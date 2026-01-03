<?php

/**
 * Copyright © 2003-2026 The Galette Team
 *
 * This file is part of Galette (https://galette.eu).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace GaletteAuto\Controllers;

use Galette\Repository\Members;
use GaletteAuto\Auto;
use GaletteAuto\Autos;
use GaletteAuto\History;
use GaletteAuto\Model;
use GaletteAuto\Picture;
use Laminas\Db\ResultSet\ResultSet;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Galette\Controllers\AbstractPluginController;
use Galette\Entity\Adherent;
use GaletteAuto\Filters\ModelsList;
use GaletteAuto\Filters\AutosList;
use GaletteAuto\Repository\Models;
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

    private bool $mine = false;
    private bool $public = false;

    private int $id_adh;

    /**
     * Check ACLs for specific member
     *
     * @param string|false|null $redirect Path to redirect to (myVehiclesList per default)
     */
    protected function checkAclsFor(Response $response, int $id_adh, string|false|null $redirect = null): bool|Response
    {
        //maybe should this be a middleware... but I do not know how to pass redirect :/
        if (
            $this->login->id != $id_adh
            && !$this->login->isAdmin()
            && !$this->login->isStaff()
        ) {
            $deps = [
                'picture'   => false,
                'dues'      => false
            ];
            $member = new Adherent($this->zdb, $id_adh, $deps);
            if (!$this->login->isGroupManager(array_keys($member->groups))) {
                //no right to see requested member.
                if ($redirect === false) {
                    return false;
                }

                $this->flash->addMessage(
                    'error_detected',
                    _T("You do not have enough privileges.", "auto")
                );

                if ($redirect === null) {
                    $redirect = $this->routeparser->urlFor('myVehiclesList');
                }
                return $response
                    ->withStatus(403)
                    ->withHeader('Location', $redirect);
            }
        }
        return true;
    }

    /**
     * Vehicle photo
     *
     * @param ?int $id Vehicle id
     */
    public function vehiclePhoto(Request $request, Response $response, ?int $id = null): Response
    {
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
        $this->public = true;
        return $this->vehiclesList($request, $response, $option, $value);
    }

    /**
     * List my vehicles
     *
     * @param string|null $option Either 'page' or 'order'
     * @param int|null    $value  Option value
     */
    public function myVehiclesList(Request $request, Response $response, ?string $option = null, ?int $value = null): Response
    {
        $this->id_adh = $this->login->id;
        $this->mine = true;
        return $this->vehiclesList($request, $response, $option, $value);
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
        $this->id_adh = $id;
        return $this->vehiclesList($request, $response, $option, $value);
    }

    /**
     * List vehicles
     *
     * @param string|null $option Either 'page' or 'order'
     * @param int|null    $value  Option value
     */
    public function vehiclesList(Request $request, Response $response, ?string $option = null, ?int $value = null): Response
    {
        $get = $request->getQueryParams();
        $id_adh = null;
        if (!empty($this->id_adh)) {
            $id_adh = (int)$this->id_adh;
            $this->checkAclsFor($response, $id_adh);
        }

        $auto = new Autos($this->plugins, $this->zdb);
        $afilters = $this->session->vehicles_filters ?? new AutosList();

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
        if ($this->mine === true) {
            $title = _T("My cars", "auto");
        } elseif ($id_adh !== null) {
            $title = _T("Member's cars", "auto");
        }

        $params = [
            'page_title'    => $title,
            'title'         => _T("Vehicles list", "auto"),
            'show_mine'     => $this->mine,
            'require_dialog' => true
        ];

        if ($id_adh === null) {
            $params['autos'] = $auto->getList(true, $this->mine, null, $afilters, null, $this->public);
        } else {
            $params['id_adh'] = $id_adh;
            $params['autos'] = $auto->getMemberList($id_adh, $afilters);
        }
        $params['count_vehicles'] = $auto->getCount();

        $this->session->vehicles_filters = $afilters;

        //assign pagination variables to the template and add pagination links
        $afilters->setViewPagination($this->routeparser, $this->view);

        // display page
        $this->view->render(
            $response,
            $this->getTemplate($this->public ? 'public_vehicles_list' : 'vehicles_list'),
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
            $auto->load($id);
            $this->checkAclsFor($response, $auto->owner->id);
        } else {
            $get = $request->getQueryParams();
            if (
                isset($get['id_adh'])
                && ($this->login->isAdmin() || $this->login->isStaff())
            ) {
                $auto->owner_id = (int)$get['id_adh'];
            } else {
                $auto->appropriateCar($this->login);
            }
        }

        if ($this->session->auto !== null) {
            $auto->check($this->session->auto);
            $this->session->auto = null;
        }

        $title = ($is_new)
            ? _T("New vehicle", "auto")
            : str_replace('%s', $auto->name, _T("Change vehicle '%s'", "auto"));

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
            'models'            => $models->getList($auto->model->brand->id),
            'brands'            => $auto->model->brand->getList(),
            'colors'            => $auto->color->getList(),
            'bodies'            => $auto->body->getList(),
            'transmissions'     => $auto->transmission->getList(),
            'finitions'         => $auto->finition->getList(),
            'states'            => $auto->state->getList(),
            'fuels'             => $auto->listFuels(),
            'time'              => time(),
            'required'          => $auto->getRequired()
        ];

        // members
        $m = new Members();
        $oid = null;
        if ($auto->owner->id > 0) {
            $oid = $auto->owner->id;
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

        if (isset($post['id_adh'])) {
            $this->checkAclsFor($response, (int)$post['id_adh']);
        }

        $auto = new Auto($this->plugins, $this->zdb);
        if (!$is_new) {
            $auto->load((int)$post[Auto::PK]);
        }

        $res = $auto->check($post);
        if ($res !== true) {
            $error_detected = $auto->getErrors();
        }

        $route = $this->routeparser->urlFor('vehiclesList');
        //if no errors were thrown, we can store the car
        if (count($error_detected) == 0) {
            if (!$auto->store($is_new)) {
                $error_detected[] = _T("- An error has occurred while saving vehicle in the database.", "auto");
            } else {
                $success_detected[] = _T("Vehicle has been saved!", "auto");
                $id_adh = $auto->owner->id;
                if (!$this->checkAclsFor($response, $id_adh, false) || $this->login->id == $id_adh) {
                    $route = $this->routeparser->urlFor('myVehiclesList');
                }
                if (!$auto->handleFiles($request->getUploadedFiles())) {
                    $warning_detected = $auto->getErrors();
                }
            }
        }

        if (count($error_detected) > 0) {
            //store entity in session
            $this->session->auto = $post;
            if ($action === 'add') {
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
        $apk = Auto::PK;
        $history = new History($this->zdb, $id);
        $auto = new Auto($this->plugins, $this->zdb);
        $auto->load($history->$apk);
        $this->checkAclsFor($response, $auto->owner->id);

        $params = [
            'entries'       => $history->getEntries(),
            'page_title'    => str_replace('%d', (string)$history->$apk, _T("History of car #%d", "auto")),
            'mode'          => $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest' ? 'ajax' : ''
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
        $auto->load($id);
        $id_adh = $auto->owner->id;
        $this->checkAclsFor($response, $id_adh);

        $route = $this->routeparser->urlFor('vehiclesList');
        if (!$this->checkAclsFor($response, $id_adh, false) || $this->login->id == $id_adh) {
            $route = $this->routeparser->urlFor('myVehiclesList');
        }

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
                'mode'          => $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest' ? 'ajax' : '',
                'page_title'    => sprintf(
                    _T('Remove vehicle %1$s', 'auto'),
                    $auto->name
                ),
                'form_url'      => $this->routeparser->urlFor('doRemoveVehicle', ['id' => (string)$auto->id]),
                'cancel_uri'    => $route,
                'data'          => $data
            ]
        );
        return $response;
    }

    /**
     * Remove vehicles confirmation page
     */
    public function removeVehicles(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();
        $route = $this->routeparser->urlFor('vehiclesList');
        $ids = $this->session->filter_vehicles ?? $post['entries_sel'];

        $auto = new Auto($this->plugins, $this->zdb);
        $auto->load((int)$ids[0]);
        $id_adh = $auto->owner->id;
        $this->checkAclsFor($response, $id_adh);

        $id_adh = $auto->owner->id;

        if (!$this->checkAclsFor($response, $id_adh, false) || $this->login->id == $id_adh) {
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
                'mode'          => $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest' ? 'ajax' : '',
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
            if (!is_array($post['id'])) {
                $ids = (array)$post['id'];
            } else {
                $ids = $post['id'];
            }

            $autos = new Autos($this->plugins, $this->zdb);
            $del = $autos->removeVehicles($ids);

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

        $this->session->vehicles_filter = $filters;

        return $response
            ->withStatus(301)
            ->withHeader(
                'Location',
                $this->routeparser->urlFor('vehiclesList')
            );
    }
}
