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

namespace GaletteAuto\Controllers\Crud;

use DI\Attribute\Inject;
use Galette\Controllers\AbstractPluginController;
use GaletteAuto\AbstractObject;
use GaletteAuto\Body;
use GaletteAuto\Brand;
use GaletteAuto\Color;
use GaletteAuto\Finition;
use GaletteAuto\State;
use GaletteAuto\Transmission;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use GaletteAuto\Filters\ModelsList;
use GaletteAuto\Filters\PropertiesList;
use GaletteAuto\Repository\Models;

/**
 * Galette Auto plugin controller for properties (brands, models, colors, ...)
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class PropertiesController extends AbstractPluginController
{
    /**
     * @var array<string, mixed>
     */
    #[Inject("Plugin Galette Auto")]
    protected array $module_info;

    /**
     * List brands
     *
     * @param string|null     $option One of 'page' or 'order'
     * @param string|int|null $value  Value of the option
     */
    public function brandsList(
        Request $request,
        Response $response,
        ?string $option = null,
        string|int|null $value = null
    ): Response {
        return $this->propertiesList($request, $response, 'brands', $option, $value);
    }

    /**
     * List colors
     *
     * @param string|null     $option One of 'page' or 'order'
     * @param string|int|null $value  Value of the option
     */
    public function colorsList(
        Request $request,
        Response $response,
        ?string $option = null,
        int|string|null $value = null
    ): Response {
        return $this->propertiesList($request, $response, 'colors', $option, $value);
    }

    /**
     * List states
     *
     * @param string|null     $option One of 'page' or 'order'
     * @param string|int|null $value  Value of the option
     */
    public function statesList(
        Request $request,
        Response $response,
        ?string $option = null,
        string|int|null $value = null
    ): Response {
        return $this->propertiesList($request, $response, 'states', $option, $value);
    }

    /**
     * List finitions
     *
     * @param string|null     $option One of 'page' or 'order'
     * @param string|int|null $value  Value of the option
     */
    public function finitionsList(
        Request $request,
        Response $response,
        ?string $option = null,
        string|int|null $value = null
    ): Response {
        return $this->propertiesList($request, $response, 'finitions', $option, $value);
    }

    /**
     * List bodies
     *
     * @param string|null     $option One of 'page' or 'order'
     * @param string|int|null $value  Value of the option
     */
    public function bodiesList(
        Request $request,
        Response $response,
        ?string $option = null,
        string|int|null $value = null
    ): Response {
        return $this->propertiesList($request, $response, 'bodies', $option, $value);
    }

    /**
     * List transmissions
     *
     * @param string|null     $option One of 'page' or 'order'
     * @param string|int|null $value  Value of the option
     */
    public function transmissionsList(
        Request $request,
        Response $response,
        ?string $option = null,
        string|int|null $value = null
    ): Response {
        return $this->propertiesList($request, $response, 'transmissions', $option, $value);
    }

    /**
     * List properties
     *
     * @param string          $property Property name
     * @param string|null     $option   One of 'page' or 'order'
     * @param string|int|null $value    Value of the option
     */
    protected function propertiesList(
        Request $request,
        Response $response,
        string $property,
        ?string $option = null,
        string|int|null $value = null
    ): Response {
        $get = $request->getQueryParams();

        switch ($property) {
            case 'colors':
                $obj = new Color($this->zdb);
                $title = _T("Colors list", "auto");
                $add_text = _T("Add new color", "auto");
                break;
            case 'states':
                $obj = new State($this->zdb);
                $title = _T("States list", "auto");
                $add_text = _T("Add new state", "auto");
                break;
            case 'finitions':
                $obj = new Finition($this->zdb);
                $title = _T("Finitions list", "auto");
                $add_text = _T("Add new finition", "auto");
                break;
            case 'bodies':
                $obj = new Body($this->zdb);
                $title = _T("Bodies list", "auto");
                $add_text = _T("Add new body", "auto");
                break;
            case 'transmissions':
                $obj = new Transmission($this->zdb);
                $title = _T("Transmissions list", "auto");
                $add_text = _T("Add new transmission", "auto");
                break;
            case 'brands':
                $obj = new Brand($this->zdb);
                $title = _T("Brands list", "auto");
                $add_text = _T("Add new brand", "auto");
                $can_show = true;
                break;
            default:
                throw new \RuntimeException('Unknown property ' . $property);
        }

        $filters = $this->getFilters($obj);
        if (isset($get['nbshow']) && is_numeric($get['nbshow'])) {
            $filters->show = $get['nbshow'];
        }
        $obj->setFilters($filters);

        switch ($option) {
            case 'page':
                $filters->current_page = (int)$value;
                break;
            case 'order':
                $filters->orderby = $value;
                break;
        }

        $this->saveFilters($obj, $filters);

        $params = [
            'page_title'    => $title,
            'list'          => $obj->getList(),
            'set'           => $property,
            'field_name'    => $obj->getFieldLabel(),
            'add_text'      => $add_text,
            'obj'           => $obj,
            'require_dialog' => true
        ];

        //assign pagination variables to the template and add pagination links
        $filters->setViewPagination($this->routeparser, $this->view, false);

        if (isset($can_show)) {
            $params['show'] = $can_show;
        }

        // display page
        $this->view->render(
            $response,
            $this->getTemplate('object_list'),
            $params
        );
        return $response;
    }

    /**
     * Filtering
     *
     * @param string $property Property name
     */
    public function filter(Request $request, Response $response, string $property): Response
    {
        $post = $request->getParsedBody();
        $class = '\GaletteAuto\\' . ucwords($property);
        $filters = $this->getFilters($class);

        if (isset($post['clear_filter'])) {
            $filters->reinit();
        } else {
            if (isset($post['nbshow']) && is_numeric($post['nbshow'])) {
                $filters->show = $post['nbshow'];
            }
        }

        $this->saveFilters($class, $filters);

        return $response
            ->withStatus(301)
            ->withHeader(
                'Location',
                $class::getListRoute($this->routeparser, $property)
            );
    }

    /**
     * Add property
     *
     * @param string $property Property name
     */
    public function propertyAdd(Request $request, Response $response, string $property): Response
    {
        return $this->propertyEdit($response, $property, null, 'add');
    }

    /**
     * Add/edit property
     *
     * @param string $property Property name
     * @param ?int   $id       Property ID, if any
     * @param string $action   'add' or 'edit'
     */
    public function propertyEdit(Response $response, string $property, ?int $id = null, string $action = 'edit'): Response
    {
        $is_new = ($action === 'add');

        $classname = AbstractObject::getClassForPropName($property);
        $object = new $classname($this->zdb);
        if ($is_new) {
            $title = _T("New", "auto");
        } else {
            $object->load($id);
            $title = str_replace(
                '%s',
                $object->{$object::FIELD},
                _T("Change '%s'", "auto")
            );
        }

        $session_oname = 'auto_' . $property;
        if ($this->session->$session_oname !== null) {
            $object = $this->session->$session_oname;
            $this->session->$session_oname = null;
        }

        $params = [
            'page_title'    => $title,
            'mode'          => ($is_new ? 'new' : 'modif'),
            'obj'           => $object,
            'set'           => $property,
        ];

        // display page
        $this->view->render(
            $response,
            $this->getTemplate('object'),
            $params
        );
        return $response;
    }

    /**
     * Do add property
     *
     * @param string $property Property name
     */
    public function doPropertyAdd(
        Request $request,
        Response $response,
        string $property
    ): Response {
        return $this->doPropertyEdit($request, $response, $property, null, 'add');
    }

    /**
     * Do add/edit property
     *
     * @param string $property Property name
     * @param ?int   $id       Property ID, if any
     * @param string $action   'add' or 'edit'
     */
    public function doPropertyEdit(
        Request $request,
        Response $response,
        string $property,
        ?int $id = null,
        string $action = 'edit',
    ): Response {
        $classname = AbstractObject::getClassForPropName($property);
        $object = new $classname($this->zdb);

        $post = $request->getParsedBody();
        $is_new = ($action === 'add');

        $error_detected = [];

        if (!$is_new) {
            if (isset($post[$object->pk])) {
                $object->load((int)$post[$object->pk]);
            } else {
                $error_detected[]
                    = _T("- No id provided for modifying this record! (internal)", "auto");
            }
        }

        $value = $post[$object->field] ?? null;
        if ($value == null) {
            $error_detected[] = _T("- You must provide a value!", "auto");
        } else {
            $object->value = $value;
        }

        if (count($error_detected) == 0) {
            $res = $object->store($is_new);
            if (!$res) {
                $error_detected[]
                    = _T("- An error occurred while saving record. Please try again.", "auto");
            } else {
                $msg = str_replace(
                    '%property',
                    $object->getFieldLabel(),
                    $is_new ? _T("New %property has been added!", "auto")
                    : _T("%property has been saved!", "auto")
                );
                $this->flash->addMessage(
                    'success_detected',
                    $msg
                );
            }
        }

        $route = AbstractObject::getListRoute($this->routeparser, $property);

        if (count($error_detected) > 0) {
            //store entity in session
            $session_oname = 'auto_' . $property;
            $this->session->$session_oname = $object;
            if (!$is_new) {
                $id = $post[$object->pk];
            }
            $route = $this->routeparser->urlFor(
                'propertyEdit',
                [
                    'action' => $action,
                    'property' => $property,
                    'id' => $id
                ]
            );

            foreach ($error_detected as $error) {
                $this->flash->addMessage(
                    'error_detected',
                    $error
                );
            }
        }

        return $response
            ->withStatus(301)
            ->withHeader('Location', $route);
    }

    /**
     * Show property
     *
     * @param string $property Property name
     * @param int    $id       Property ID, if any
     */
    public function propertyShow(Response $response, string $property, int $id): Response
    {
        $classname = AbstractObject::getClassForPropName($property);
        $object = new $classname($this->zdb);
        $object->load($id);
        $title = str_replace(
            '%s',
            $object->{$object::FIELD},
            _T("Show '%s' brand", "auto")
        );

        $params = [
            'page_title'    => $title,
            'obj'           => $object
        ];

        if ($object instanceof \GaletteAuto\Brand) {
            $models = new Models(
                $this->zdb,
                $this->preferences,
                $this->login,
                new ModelsList()
            );
            $params['models'] = $models->getList($object->id);
        }

        // display page
        $this->view->render(
            $response,
            $this->getTemplate('object_show'),
            $params
        );
        return $response;
    }

    /**
     * Remove property confirmation page
     *
     * @param string $property Property name
     * @param int    $id       Property id
     */
    public function removeProperty(Request $request, Response $response, string $property, int $id): Response
    {
        $classname = AbstractObject::getClassForPropName($property);
        $object = new $classname($this->zdb);
        $object->load($id);

        $route = AbstractObject::getListRoute($this->routeparser, $property);

        $data = [
            'id'            => $id,
            'property'      => $property,
            'redirect_uri'  => $route
        ];

        // display page
        $this->view->render(
            $response,
            'modals/confirm_removal.html.twig',
            [
                'type'          => $object->getFieldLabel(),
                'mode'          => $this->isAjax($request) ? 'ajax' : '',
                'page_title'    => sprintf(
                    _T('Remove %1$s %2$s', 'auto'),
                    $object->getFieldLabel(),
                    $object->value
                ),
                'form_url'      => $this->routeparser->urlFor(
                    'doRemoveProperty',
                    ['property' => $property, 'id' => $object->id]
                ),
                'cancel_uri'    => $route,
                'data'          => $data
            ]
        );
        return $response;
    }

    /**
     * Do remove property
     *
     * @param string $property Property name
     * @param ?int   $id       Property id
     */
    public function doRemoveProperty(Request $request, Response $response, string $property, ?int $id = null): Response
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

            $classname = AbstractObject::getClassForPropName($property);
            $object = new $classname($this->zdb);

            try {
                $object->delete($ids);

                switch ($property) {
                    case 'colors':
                    case 'color':
                        $message = _Tn('%1$s color has been successfully deleted.', '%1$s colors have been successfully deleted.', count($ids), 'auto');
                        break;
                    case 'states':
                    case 'state':
                        $message = _Tn('%1$s state has been successfully deleted.', '%1$s states have been successfully deleted.', count($ids), 'auto');
                        break;
                    case 'finitions':
                    case 'finition':
                        $message = _Tn('%1$s finition has been successfully deleted.', '%1$s finitions have been successfully deleted.', count($ids), 'auto');
                        break;
                    case 'bodies':
                    case 'body':
                        $message = _Tn('%1$s body has been successfully deleted.', '%1$s bodies have been successfully deleted.', count($ids), 'auto');
                        break;
                    case 'transmissions':
                    case 'transmission':
                        $message = _Tn('%1$s transmission has been successfully deleted.', '%1$s transmissions have been successfully deleted.', count($ids), 'auto');
                        break;
                    case 'brands':
                    case 'brand':
                        $message = _Tn('%1$s brand has been successfully deleted.', '%1$s brands have been successfully deleted.', count($ids), 'auto');
                        break;
                    default:
                        throw new \RuntimeException('Unknown property ' . $property);
                }

                $this->flash->addMessage(
                    'success_detected',
                    sprintf($message, count($ids))
                );

                $success = true;
            } catch (\Throwable $e) {
                if ($this->zdb->isForeignKeyException($e)) {
                    switch ($property) {
                        case 'colors':
                        case 'color':
                            $message = _T('This color is used by one or more vehicles, it cannot be deleted.', 'auto');
                            break;
                        case 'states':
                        case 'state':
                            $message = _T('This state is used by one or more vehicles, it cannot be deleted.', 'auto');
                            break;
                        case 'finitions':
                        case 'finition':
                            $message = _T('This finition is used by one or more vehicles, it cannot be deleted.', 'auto');
                            break;
                        case 'bodies':
                        case 'body':
                            $message = _T('This body is used by one or more vehicles, it cannot be deleted.', 'auto');
                            break;
                        case 'transmissions':
                        case 'transmission':
                            $message = _T('This transmission is used by one or more vehicles, it cannot be deleted.', 'auto');
                            break;
                        case 'brands':
                        case 'brand':
                            $message = _T('This brand is used by one or more vehicles, it cannot be deleted.', 'auto');
                            break;
                        default:
                            throw new \RuntimeException('Unknown property ' . $property);
                    }

                    $this->flash->addMessage(
                        'error_detected',
                        $message
                    );
                } else {
                    switch ($property) {
                        case 'colors':
                        case 'color':
                            $message = _T('An error occurred trying to remove color :/', 'auto');
                            break;
                        case 'states':
                        case 'state':
                            $message = _T('An error occurred trying to remove state :/', 'auto');
                            break;
                        case 'finitions':
                        case 'finition':
                            $message = _T('An error occurred trying to remove finition :/', 'auto');
                            break;
                        case 'bodies':
                        case 'body':
                            $message = _T('An error occurred trying to remove body :/', 'auto');
                            break;
                        case 'transmissions':
                        case 'transmission':
                            $message = _T('An error occurred trying to remove transmission :/', 'auto');
                            break;
                        case 'brands':
                        case 'brand':
                            $message = _T('An error occurred trying to remove brand :/', 'auto');
                            break;
                        default:
                            throw new \RuntimeException('Unknown property ' . $property);
                    }

                    $this->flash->addMessage(
                        'error_detected',
                        $message
                    );
                }
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
     * Get filters
     *
     * @param AbstractObject|string $class Class name or instance
     */
    protected function getFilters(AbstractObject|string $class): PropertiesList
    {
        /** @phpstan-ignore-next-line */
        $filter_name = 'filter_auto' . $class::FIELD;
        return $this->session->$filter_name ?? new PropertiesList();
    }

    /**
     * Save filters
     *
     * @param AbstractObject|string $class   Class name or instance
     * @param PropertiesList        $filters Filters instance
     */
    protected function saveFilters(AbstractObject|string $class, PropertiesList $filters): void
    {
        /** @phpstan-ignore-next-line */
        $filter_name = 'filter_auto' . $class::FIELD;
        $this->session->$filter_name = $filters;
    }
}
