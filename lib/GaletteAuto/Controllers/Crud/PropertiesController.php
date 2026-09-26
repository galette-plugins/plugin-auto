<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
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
use GaletteAuto\Repository\Properties;

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
        return $this->propertiesList($request, $response, new Brand($this->zdb), $option, $value);
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
        return $this->propertiesList($request, $response, new Color($this->zdb), $option, $value);
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
        return $this->propertiesList($request, $response, new State($this->zdb), $option, $value);
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
        return $this->propertiesList($request, $response, new Finition($this->zdb), $option, $value);
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
        return $this->propertiesList($request, $response, new Body($this->zdb), $option, $value);
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
        return $this->propertiesList($request, $response, new Transmission($this->zdb), $option, $value);
    }

    /**
     * List properties
     *
     * @param AbstractObject  $obj    Property instance
     * @param string|null     $option One of 'page' or 'order'
     * @param string|int|null $value  Value of the option
     */
    protected function propertiesList(
        Request $request,
        Response $response,
        AbstractObject $obj,
        ?string $option = null,
        string|int|null $value = null
    ): Response {
        $get = $request->getQueryParams();

        $filters = $this->getFilters($obj);
        if (isset($get['nbshow']) && is_numeric($get['nbshow'])) {
            $filters->show = $get['nbshow'];
        }

        switch ($option) {
            case 'page':
                $filters->current_page = (int)$value;
                break;
            case 'order':
                $filters->orderby = $value;
                break;
        }

        $this->saveFilters($obj, $filters);

        $properties = $this->getRepository($obj::class, $filters);
        $params = [
            'page_title'    => $obj->getListTitle(),
            'list'          => $properties->getList(),
            'count_label'   => $obj->getCountLabel($properties->getCount()),
            'field_name'    => $obj->getFieldLabel(),
            'add_text'      => $obj->getAddText(),
            'obj'           => $obj,
            'require_dialog' => true
        ];

        //assign pagination variables to the template and add pagination links
        $filters->setViewPagination($this->routeparser, $this->view, false);

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
        $class = AbstractObject::getClassForPropName($property);
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
                $class::getListRoute($this->routeparser)
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

        $object = AbstractObject::fromPropertyName($this->zdb, $property);
        if ($is_new) {
            $title = _T("New", "auto");
        } else {
            $object->load($id);
            $title = str_replace(
                '%s',
                $object->getValue(),
                _T("Change '%s'", "auto")
            );
        }

        //value from a failed submission
        $session_oname = 'auto_' . $property . '_data';
        if (isset($this->session->$session_oname)) {
            $object->setValue((string)$this->session->$session_oname);
            unset($this->session->$session_oname);
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
        $object = AbstractObject::fromPropertyName($this->zdb, $property);

        $post = $request->getParsedBody();
        $is_new = ($action === 'add');

        $error_detected = [];

        if (!$is_new && !$object->load((int)$id)) {
            $error_detected[]
                = _T("- An error occurred while saving record. Please try again.", "auto");
        }

        $value = $post[$object->getField()] ?? null;
        if ($value == null) {
            $error_detected[] = _T("- You must provide a value!", "auto");
        } else {
            $object->setValue($value);
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

        $route = $object::getListRoute($this->routeparser);

        if (count($error_detected) > 0) {
            //store entity in session
            $session_oname = 'auto_' . $property . '_data';
            $this->session->$session_oname = (string)($value ?? '');
            if ($is_new) {
                $route = $this->routeparser->urlFor('propertyAdd', ['property' => $property]);
            } else {
                $route = $this->routeparser->urlFor(
                    'propertyEdit',
                    [
                        'property' => $property,
                        'id' => (string)$id
                    ]
                );
            }

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
        $object = AbstractObject::fromPropertyName($this->zdb, $property);
        $object->load($id);
        $title = str_replace(
            '%s',
            $object->getValue(),
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
            $params['models'] = $models->getList($object->getId());
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
        $object = AbstractObject::fromPropertyName($this->zdb, $property);
        $object->load($id);

        $route = $object::getListRoute($this->routeparser);

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
                    $object->getValue()
                ),
                'form_url'      => $this->routeparser->urlFor(
                    'doRemoveProperty',
                    ['property' => $property, 'id' => (string)$id]
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
            $ids = array_map('intval', (array)$post['id']);
            $object = AbstractObject::fromPropertyName($this->zdb, $property);

            try {
                $this->getRepository($object::class)->remove($ids);
                $this->flash->addMessage(
                    'success_detected',
                    $object->getRemovedMessage(count($ids))
                );
                $success = true;
            } catch (\Throwable $e) {
                $this->flash->addMessage(
                    'error_detected',
                    $this->zdb->isForeignKeyException($e)
                        ? $object->getInUseMessage()
                        : $object->getRemoveErrorMessage()
                );
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
     * Get properties repository
     *
     * @param class-string<AbstractObject> $class   Property class name
     * @param ?PropertiesList              $filters Filters
     */
    protected function getRepository(string $class, ?PropertiesList $filters = null): Properties
    {
        return new Properties($this->zdb, $this->preferences, $this->login, $class, $filters);
    }

    /**
     * Get filters
     *
     * @param AbstractObject|class-string<AbstractObject> $class Class name or instance
     */
    protected function getFilters(AbstractObject|string $class): PropertiesList
    {
        $filter_name = 'filter_auto' . $class::FIELD;
        return $this->session->$filter_name ?? new PropertiesList();
    }

    /**
     * Save filters
     *
     * @param AbstractObject|class-string<AbstractObject> $class   Class name or instance
     * @param PropertiesList                              $filters Filters instance
     */
    protected function saveFilters(AbstractObject|string $class, PropertiesList $filters): void
    {
        $filter_name = 'filter_auto' . $class::FIELD;
        $this->session->$filter_name = $filters;
    }
}
