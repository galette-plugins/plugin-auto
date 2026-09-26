<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\Controllers;

use Analog\Analog;
use Galette\Repository\Members;
use GaletteAuto\AbstractObject;
use GaletteAuto\Auto;
use GaletteAuto\Body;
use GaletteAuto\Brand;
use GaletteAuto\Color;
use GaletteAuto\Finition;
use GaletteAuto\History;
use GaletteAuto\Model;
use GaletteAuto\Picture;
use GaletteAuto\State;
use GaletteAuto\Transmission;
use GaletteAuto\VehicleAccess;
use Laminas\Db\ResultSet\ResultSet;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Galette\Controllers\AbstractPluginController;
use Galette\Entity\Adherent;
use GaletteAuto\Filters\ModelsList;
use GaletteAuto\Filters\AutosList;
use GaletteAuto\Repository\Models;
use GaletteAuto\Repository\Properties;
use GaletteAuto\Repository\Vehicles;
use DI\Attribute\Inject;

/**
 * Galette Auto plugin controller
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Controller extends AbstractPluginController
{
    /**
     * @var array<string, mixed>
     */
    #[Inject("Plugin Galette Auto")]
    protected array $module_info;

    /**
     * Get vehicles access rules
     */
    protected function getAccess(): VehicleAccess
    {
        return new VehicleAccess($this->zdb, $this->login, $this->preferences);
    }

    /**
     * Refuse access to a vehicle or to vehicles of a member
     *
     * @param string $log Log message
     */
    protected function accessDenied(Response $response, string $log): Response
    {
        Analog::log(
            $log . ' (user #' . $this->login->id . ')',
            Analog::WARNING
        );
        return $this->redirectWithErrors(
            $response,
            [_T("You do not have enough privileges.", "auto")],
            $this->routeparser->urlFor('myVehiclesList')
        );
    }

    /**
     * Get vehicles repository
     */
    protected function getVehicles(): Vehicles
    {
        return new Vehicles($this->plugins, $this->zdb, $this->login, $this->history);
    }

    /**
     * Get the whole list of a property
     *
     * @param class-string<AbstractObject> $class Property class name
     *
     * @return array<int, AbstractObject>
     */
    protected function getProperties(string $class): array
    {
        return (new Properties($this->zdb, $this->preferences, $this->login, $class))->getList();
    }

    /**
     * Can current user manage all the vehicles?
     *
     * @param array<int> $ids Vehicles IDs
     */
    protected function canManageVehicles(array $ids): bool
    {
        if (count($ids) === 0) {
            return false;
        }

        $owners = $this->getVehicles()->getOwners($ids);

        if (count($owners) !== count(array_unique($ids))) {
            //some vehicles do not exist
            return false;
        }

        $access = $this->getAccess();
        foreach (array_unique($owners) as $id_adh) {
            if (!$access->canManageMember($id_adh)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Can current user see a vehicle?
     *
     * @param int $id Vehicle ID
     */
    protected function canViewVehicle(int $id): bool
    {
        $select = $this->zdb->select(AUTO_PREFIX . Auto::TABLE);
        $select->columns([Adherent::PK])->where([Auto::PK => $id]);
        $row = $this->zdb->execute($select)->current();
        if ($row === null) {
            return false;
        }
        return $this->getAccess()->canViewVehicles()
            || $this->getAccess()->canManageMember((int)$row[Adherent::PK]);
    }

    /**
     * Get the vehicles list to go back to after an action on a vehicle
     *
     * @param int $id_adh Vehicle owner ID
     */
    protected function getListRoute(int $id_adh): string
    {
        if ($this->login->id == $id_adh || !$this->getAccess()->isManager()) {
            return $this->routeparser->urlFor('myVehiclesList');
        }
        return $this->routeparser->urlFor('vehiclesList');
    }

    /**
     * Vehicle photo
     *
     * @param ?int $id Vehicle id
     */
    public function vehiclePhoto(Request $request, Response $response, ?int $id = null): Response
    {
        if ($id !== null && !$this->canViewVehicle($id)) {
            //not allowed: serve default picture
            $id = null;
        }
        $picture = new Picture($this->plugins, $id);

        $response = $response->withHeader('Content-Type', $picture->getMime())
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate')
            ->withHeader('Pragma', 'public');

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, file_get_contents($picture->getPath()));
        rewind($stream);

        return $response->withBody(new \Slim\Psr7\Stream($stream));
    }

    /**
     * Public vehicles list
     *
     * @param string|null $option Either 'page' or 'order'
     * @param int|null    $value  Option value
     */
    public function publicVehiclesList(Request $request, Response $response, ?string $option = null, ?int $value = null): Response
    {
        return $this->listVehicles($request, $response, $option, $value, public: true);
    }

    /**
     * List my vehicles
     *
     * @param string|null $option Either 'page' or 'order'
     * @param int|null    $value  Option value
     */
    public function myVehiclesList(Request $request, Response $response, ?string $option = null, ?int $value = null): Response
    {
        return $this->listVehicles($request, $response, $option, $value, (int)$this->login->id, mine: true);
    }

    /**
     * List vehicles for a member
     *
     * @param int         $id     Member ID
     * @param string|null $option Either 'page' or 'order'
     * @param int|null    $value  Option value
     */
    public function memberVehiclesList(Request $request, Response $response, int $id, ?string $option = null, ?int $value = null): Response
    {
        return $this->listVehicles($request, $response, $option, $value, $id);
    }

    /**
     * List vehicles
     *
     * @param string|null $option Either 'page' or 'order'
     * @param int|null    $value  Option value
     */
    public function vehiclesList(Request $request, Response $response, ?string $option = null, ?int $value = null): Response
    {
        return $this->listVehicles($request, $response, $option, $value);
    }

    /**
     * List vehicles, of every visible member or of one of them
     *
     * @param string|null $option Either 'page' or 'order'
     * @param int|null    $value  Option value
     * @param int|null    $id_adh Member ID, null for all visible members
     * @param bool        $mine   Current user's vehicles
     * @param bool        $public Public list
     */
    protected function listVehicles(
        Request $request,
        Response $response,
        ?string $option = null,
        ?int $value = null,
        ?int $id_adh = null,
        bool $mine = false,
        bool $public = false
    ): Response {
        $get = $request->getQueryParams();
        if (empty($id_adh)) {
            //superadmin has no vehicles of its own
            $id_adh = null;
        } else {
            if (!$this->getAccess()->canManageMember($id_adh)) {
                return $this->accessDenied($response, 'Trying to list vehicles of member #' . $id_adh);
            }
        }

        $vehicles = $this->getVehicles();
        //the public page paginates on its own: a manager going there must not
        //land on the page, or the number of rows, of the management list
        $session_key = $public ? 'public_vehicles_filters' : 'vehicles_filters';
        $afilters = $this->session->$session_key ?? new AutosList();

        // Simple filters
        if ($option !== null) {
            switch ($option) {
                case 'page':
                    $afilters->current_page = (int)$value;
                    break;
                case 'order':
                    $afilters->orderby = $value;
                    break;
            }
        }

        if (isset($get["nbshow"]) && is_numeric($get["nbshow"])) {
            $afilters->show = $get["nbshow"];
        }

        $title = _T("Cars list", "auto");
        if ($mine === true) {
            $title = _T("My cars", "auto");
        } elseif ($id_adh !== null) {
            $title = _T("Member's cars", "auto");
        }

        $params = [
            'page_title'    => $title,
            'title'         => _T("Vehicles list", "auto"),
            'show_mine'     => $mine,
            'require_dialog' => true
        ];

        if ($id_adh !== null) {
            $params['id_adh'] = $id_adh;
        }
        $params['autos'] = $vehicles->getList($afilters, $id_adh, $mine, $public);
        $params['count_vehicles'] = $vehicles->getCount();

        if ($public) {
            $access = $this->getAccess();
            $params['public_owners'] = [];
            //history is shown to whoever may see it from the vehicle form
            $params['history_allowed'] = [];
            foreach ($params['autos'] as $vehicle) {
                $params['public_owners'][$vehicle->getId()] = $access->isOwnerPublic($vehicle->getOwner());
                $params['history_allowed'][$vehicle->getId()] = $this->login->isLogged()
                    && $access->canManageMember((int)$vehicle->getOwnerId());
            }
        }

        $this->session->$session_key = $afilters;

        //assign pagination variables to the template and add pagination links
        $afilters->setViewPagination($this->routeparser, $this->view);

        // display page
        $this->view->render(
            $response,
            $this->getTemplate($public ? 'public_vehicles_list' : 'vehicles_list'),
            $params
        );
        return $response;
    }

    /**
     * Show add vehicle route
     */
    public function showAddVehicle(Request $request, Response $response): Response
    {
        return $this->showAddEditVehicle($request, $response, 'add');
    }

    /**
     * Show edit vehicle route
     *
     * @param int $id Vehicle id
     */
    public function showEditVehicle(Request $request, Response $response, int $id): Response
    {
        return $this->showAddEditVehicle($request, $response, 'edit', $id);
    }

    /**
     * Show add/edit route
     *
     * @param string   $action Either 'add' or 'edit'
     * @param int|null $id     Vehicle id
     */
    public function showAddEditVehicle(Request $request, Response $response, string $action, ?int $id = null): Response
    {
        $is_new = ($action === 'add');

        $auto = new Auto($this->plugins, $this->zdb);
        if (!$is_new) {
            if (!$auto->load((int)$id) || !$this->getAccess()->canManageMember($auto->getOwnerId())) {
                return $this->accessDenied($response, 'Trying to edit vehicle #' . $id);
            }
        } else {
            $get = $request->getQueryParams();
            if (
                isset($get['id_adh'])
                && $this->getAccess()->isManager()
                && $this->getAccess()->canManageMember((int)$get['id_adh'])
            ) {
                $auto->setOwner((int)$get['id_adh']);
            } else {
                $auto->appropriateCar($this->login);
            }
        }

        if ($this->session->auto !== null) {
            $auto->check($this->session->auto, $this->getAccess());
            $this->session->auto = null;
        }

        $title = ($is_new)
            ? _T("New vehicle", "auto")
            : str_replace('%s', $auto->getName(), _T("Change vehicle '%s'", "auto"));

        $mfilters = new ModelsList();
        $models = new Models(
            $this->zdb,
            $this->preferences,
            $this->login,
            $mfilters
        );

        $params = [
            'page_title'        => $title,
            'mode'              => (($is_new) ? 'new' : 'modif'),
            'require_calendar'  => true,
            'require_dialog'    => true,
            'car'               => $auto,
            'models'            => $models->getList($auto->getModel()->getBrand()->getId()),
            'brands'            => $this->getProperties(Brand::class),
            'colors'            => $this->getProperties(Color::class),
            'bodies'            => $this->getProperties(Body::class),
            'transmissions'     => $this->getProperties(Transmission::class),
            'finitions'         => $this->getProperties(Finition::class),
            'states'            => $this->getProperties(State::class),
            'fuels'             => $auto->listFuels(),
            'time'              => time(),
            'required'          => $auto->getRequired()
        ];

        // members
        $m = new Members();
        $oid = null;
        if ($auto->getOwnerId() > 0) {
            $oid = $auto->getOwnerId();
        }
        $members = $m->getDropdownMembers(
            $this->zdb,
            $this->login,
            $oid
        );

        $params['members'] = [
            'filters'   => $m->getFilters(),
            'count'     => $m->getCount()
        ];

        if (count($members)) {
            $params['members']['list'] = $members;
        }
        $params['autocomplete'] = true;

        // display page
        $this->view->render(
            $response,
            $this->getTemplate('vehicles'),
            $params
        );
        return $response;
    }

    /**
     * Do add vehicle route
     */
    public function doAddVehicle(Request $request, Response $response): Response
    {
        return $this->doAddEditVehicle($request, $response, 'new');
    }

    /**
     * Do edit vehicle route
     *
     * @param int $id Vehicle id
     */
    public function doEditVehicle(Request $request, Response $response, int $id): Response
    {
        return $this->doAddEditVehicle($request, $response, 'edit', $id);
    }

    /**
     * Do add/edit route
     *
     * @param string   $action Either 'add' or 'edit'
     * @param int|null $id     Vehicle id
     */
    public function doAddEditVehicle(Request $request, Response $response, string $action = 'edit', ?int $id = null): Response
    {
        $post = $request->getParsedBody();

        $is_new = ($action === 'add' || $action === 'new');

        // initialize warnings
        $error_detected = [];
        $warning_detected = [];
        $success_detected = [];

        $auto = new Auto($this->plugins, $this->zdb);
        if (!$is_new) {
            if (!$auto->load((int)$id) || !$this->getAccess()->canManageMember($auto->getOwnerId())) {
                return $this->accessDenied($response, 'Trying to store vehicle #' . $id);
            }
        }

        $res = $auto->check($post, $this->getAccess());
        if ($res !== true) {
            $error_detected = $auto->getErrors();
        }

        $route = $this->routeparser->urlFor('vehiclesList');
        //if no errors were thrown, we can store the car
        if (count($error_detected) == 0) {
            try {
                $this->getVehicles()->store($auto);
                $stored = true;
            } catch (\Throwable $e) {
                $stored = false;
            }
            if (!$stored) {
                $error_detected[] = _T("- An error has occurred while saving vehicle in the database.", "auto");
            } else {
                $success_detected[] = _T("Vehicle has been saved!", "auto");
                $route = $this->getListRoute($auto->getOwnerId());
                if (!$auto->handleFiles($request->getUploadedFiles())) {
                    $warning_detected = $auto->getErrors();
                }
            }
        }

        if (count($error_detected) > 0) {
            //store entity in session
            $this->session->auto = $post;
            if ($is_new) {
                $route = $this->routeparser->urlFor('vehicleAdd');
            } else {
                $route = $this->routeparser->urlFor('vehicleEdit', ['id' => (string)$id]);
            }

            foreach ($error_detected as $error) {
                $this->flash->addMessage(
                    'error_detected',
                    $error
                );
            }
        }

        if (count($warning_detected) > 0) {
            foreach ($warning_detected as $warning) {
                $this->flash->addMessage(
                    'warning_detected',
                    $warning
                );
            }
        }

        if (count($success_detected) > 0) {
            foreach ($success_detected as $success) {
                $this->flash->addMessage(
                    'success_detected',
                    $success
                );
            }
        }

        return $response
            ->withStatus(301)
            ->withHeader('Location', $route);
    }

    /**
     * Show vehicle history
     *
     * @param int $id Vehicle id
     */
    public function vehicleHistory(Request $request, Response $response, int $id): Response
    {
        $history = new History($this->zdb, $id);
        $auto = new Auto($this->plugins, $this->zdb);
        if (!$auto->load((int)$history->getCarId()) || !$this->getAccess()->canManageMember($auto->getOwnerId())) {
            return $this->accessDenied($response, 'Trying to show history of vehicle #' . $id);
        }

        $params = [
            'entries'       => $history->getEntries(),
            'page_title'    => str_replace('%d', (string)$history->getCarId(), _T("History of car #%d", "auto")),
            'mode'          => $this->isAjax($request) ? 'ajax' : ''
        ];

        // display page
        $this->view->render(
            $response,
            $this->getTemplate('history'),
            $params
        );
        return $response;
    }

    /**
     * List models from ajax call
     */
    public function ajaxModels(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();
        $list = [];
        $models = new Models(
            $this->zdb,
            $this->preferences,
            $this->login,
            new ModelsList()
        );

        $id_brand = null;
        if (isset($post['brand']) && $post['brand'] != '') {
            $id_brand = (int)$post['brand'];
        }
        /** @var array<int, Model>|ResultSet $list */
        $list = $models->getList($id_brand, false);
        return $this->withJson($response, $list->toArray());
    }

    /**
     * Remove vehicle confirmation page
     *
     * @param int $id Vehicle ID
     */
    public function removeVehicle(Request $request, Response $response, int $id): Response
    {
        $auto = new Auto($this->plugins, $this->zdb);
        if (!$auto->load($id) || !$this->getAccess()->canManageMember($auto->getOwnerId())) {
            return $this->accessDenied($response, 'Trying to remove vehicle #' . $id);
        }
        $route = $this->getListRoute($auto->getOwnerId());

        $data = [
            'id'            => $id,
            'redirect_uri'  => $route
        ];

        // display page
        $this->view->render(
            $response,
            'modals/confirm_removal.html.twig',
            [
                'type'          => _T("Vehicle", "auto"),
                'mode'          => $this->isAjax($request) ? 'ajax' : '',
                'page_title'    => sprintf(
                    _T('Remove vehicle %1$s', 'auto'),
                    $auto->getName()
                ),
                'form_url'      => $this->routeparser->urlFor('doRemoveVehicle', ['id' => (string)$auto->getId()]),
                'cancel_uri'    => $route,
                'data'          => $data
            ]
        );
        return $response;
    }

    /**
     * Batch actions on vehicles list
     */
    public function batch(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();
        $list_route = $this->routeparser->urlFor(
            $this->getAccess()->isManager() ? 'vehiclesList' : 'myVehiclesList'
        );

        if (empty($post['entries_sel'])) {
            return $this->redirectWithErrors(
                $response,
                [_T("No vehicle was selected, please check at least one name.", "auto")],
                $list_route
            );
        }

        $this->session->filter_vehicles = array_map('intval', (array)$post['entries_sel']);
        if (isset($post['delete'])) {
            return $response
                ->withStatus(301)
                ->withHeader('Location', $this->routeparser->urlFor('removeVehicles'));
        }

        Analog::log(
            'Unknown batch action on vehicles list: ' . implode(', ', array_keys($post)),
            Analog::WARNING
        );
        return $response
            ->withStatus(301)
            ->withHeader('Location', $list_route);
    }

    /**
     * Remove vehicles confirmation page
     */
    public function removeVehicles(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();
        $route = $this->routeparser->urlFor('vehiclesList');
        $ids = $post['entries_sel'] ?? $this->session->filter_vehicles ?? [];
        $ids = array_map('intval', (array)$ids);

        if (!$this->canManageVehicles($ids)) {
            return $this->accessDenied($response, 'Trying to remove vehicles #' . implode(', #', $ids));
        }
        if (!$this->getAccess()->isManager()) {
            $route = $this->routeparser->urlFor('myVehiclesList');
        }

        $data = [
            'id'            => $ids,
            'redirect_uri'  => $route
        ];

        // display page
        $this->view->render(
            $response,
            'modals/confirm_removal.html.twig',
            [
                'type'          => _T("Vehicle", "auto"),
                'mode'          => $this->isAjax($request) ? 'ajax' : '',
                'page_title'    => _T('Remove vehicles', 'auto'),
                'message'       => str_replace(
                    '%count',
                    (string)count($data['id']),
                    _Tn('You are about to remove %count vehicle.', 'You are about to remove %count vehicles.', count($data['id']), 'auto')
                ),
                'form_url'      => $this->routeparser->urlFor('doRemoveVehicle'),
                'cancel_uri'    => $route,
                'data'          => $data
            ]
        );
        return $response;
    }

    /**
     * Do remove vehicles
     */
    public function doRemoveVehicle(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();
        $ajax = isset($post['ajax']) && $post['ajax'] === 'true';
        $success = false;

        $uri = $post['redirect_uri']
            ?? $this->routeparser->urlFor('slash');

        if (!isset($post['confirm'])) {
            $this->flash->addMessage(
                'error_detected',
                _T("Removal has not been confirmed!")
            );
        } else {
            $ids = array_map('intval', (array)($post['id'] ?? []));
            if (!$this->canManageVehicles($ids)) {
                return $this->accessDenied($response, 'Trying to remove vehicles #' . implode(', #', $ids));
            }

            try {
                $this->getVehicles()->remove($ids);
                $del = true;
            } catch (\Throwable $e) {
                $del = false;
            }
            unset($this->session->filter_vehicles);

            if ($del !== true) {
                $error_detected = _T("An error occurred trying to remove vehicles :/", "auto");

                $this->flash->addMessage(
                    'error_detected',
                    $error_detected
                );
            } else {
                $success_detected = str_replace(
                    '%count',
                    (string)count($ids),
                    _T("%count vehicles have been successfully deleted.", "auto")
                );

                $this->flash->addMessage(
                    'success_detected',
                    $success_detected
                );

                $success = true;
            }
        }

        if (!$ajax) {
            return $response
                ->withStatus(301)
                ->withHeader('Location', $uri);
        } else {
            return $this->withJson(
                $response,
                [
                    'success'   => $success
                ]
            );
        }
    }

    /**
     * Filtering
     */
    public function filter(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();

        $filters = $this->session->vehicles_filters ?? new AutosList();

        if (isset($post['clear_filter'])) {
            $filters->reinit();
        } else {
            if (isset($post['nbshow']) && is_numeric($post['nbshow'])) {
                $filters->show = $post['nbshow'];
            }
        }

        $this->session->vehicles_filters = $filters;

        return $response
            ->withStatus(301)
            ->withHeader(
                'Location',
                $this->routeparser->urlFor(
                    $this->getAccess()->isManager() ? 'vehiclesList' : 'myVehiclesList'
                )
            );
    }
}
