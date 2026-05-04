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

use Galette\Middleware\Authenticate;
use GaletteAuto\Controllers\Controller;
use GaletteAuto\Controllers\Crud\PropertiesController;
use GaletteAuto\Controllers\Crud\ModelsController;
use Slim\Routing\RouteParser;

//Constants and classes from plugin
require_once $module['root'] . '/_config.inc.php';

$app->get(
    '/vehicle/photo[/{id:\d+}]',
    [Controller::class, 'vehiclePhoto']
)->setName('vehiclePhoto');

$app->get(
    '/vehicles[/{option:page|order}/{value:\d+}]',
    [Controller::class, 'vehiclesList']
)->setName('vehiclesList')->add(Authenticate::class);

$app->post(
    '/vehicle/filter',
    [Controller::class, 'filter']
)->setName('vehiclesFilter')->add(Authenticate::class);

$app->get(
    '/member/{id:\d+}/vehicles[/{option:page|order}/{value:\d+}]',
    [Controller::class, 'memberVehiclesList']
)->setName('memberVehiclesList')->add(Authenticate::class);

$app->group('/public', function () use ($app): void {
    $app->get(
        '/public/vehicles',
        [Controller::class, 'publicVehiclesList']
    )->setName('publicVehiclesList');
})->add(\Galette\Middleware\PublicPages::class);

$app->get(
    '/my-vehicles',
    [Controller::class, 'myVehiclesList']
)->setName('myVehiclesList')->add(Authenticate::class);

$app->get(
    '/vehicle/add',
    [Controller::class, 'showAddVehicle']
)->setName('vehicleAdd')->add(Authenticate::class);

$app->get(
    '/vehicle/edit/{id:\d+}',
    [Controller::class, 'showEditVehicle']
)->setName('vehicleEdit')->add(Authenticate::class);

$app->post(
    '/vehicle/add',
    [Controller::class, 'doAddVehicle']
)->setName('doVehicleAdd')->add(Authenticate::class);

$app->post(
    '/vehicle/edit/{id:\d+}',
    [Controller::class, 'doEditVehicle']
)->setName('doVehicleEdit')->add(Authenticate::class);

$app->get(
    '/vehicle/history/{id:\d+}',
    [Controller::class, 'vehicleHistory']
)->setName('vehicleHistory')->add(Authenticate::class);

$app->post(
    '/ajax/models',
    [Controller::class, 'ajaxModels']
)->setName('ajaxModels')->add(Authenticate::class);

$app->get(
    '/vehicle/remove/{id:\d+}',
    [Controller::class, 'removeVehicle']
)->setName('removeVehicle')->add(Authenticate::class);

$app->map(
    ['GET', 'POST'],
    '/vehicles/remove',
    [Controller::class, 'removeVehicles']
)->setName('removeVehicles')->add(Authenticate::class);

$app->post(
    '/vehicle/remove[/{id:\d+}]',
    [Controller::class, 'doRemoveVehicle']
)->setName('doRemoveVehicle')->add(Authenticate::class);

//Batch actions on vehicles list
$app->post(
    '/vehicles/batch',
    function ($request, $response) use ($app, $container) {
        $post = $request->getParsedBody();

        if (isset($post['entries_sel'])) {
            $container->get('session')->filter_vehicles = $post['entries_sel'];

            if (isset($post['delete'])) {
                return $response
                    ->withStatus(301)
                    ->withHeader('Location', $container->get(RouteParser::class)->urlFor('removeVehicles'));
            }
        } else {
            $app->flash->addMessage(
                'error_detected',
                _T("No vehicle was selected, please check at least one name.", "auto")
            );

            return $response
                ->withStatus(301)
                ->withHeader('Location', $container->get(RouteParser::class)->urlFor('myVehiclesList'));
        }
    }
)->setName('batch-vehicleslist')->add(Authenticate::class);

$app->get(
    '/models[/{option:page|order}/{value:\d+}]',
    [ModelsController::class, 'list']
)->setName('modelsList')->add(Authenticate::class);

$app->post(
    '/models/filter',
    [ModelsController::class, 'filter']
)->setName('modelsFilter')->add(Authenticate::class);

$app->get(
    '/models/add',
    [ModelsController::class, 'add']
)->setName('modelAdd')->add(Authenticate::class);

$app->get(
    '/models/edit/{id:\d+}',
    [ModelsController::class, 'edit']
)->setName('modelEdit')->add(Authenticate::class);

$app->post(
    '/models/add',
    [ModelsController::class, 'doAdd']
)->setName('doModelAdd')->add(Authenticate::class);

$app->post(
    '/models/edit/{id:\d+}',
    [ModelsController::class, 'doEdit']
)->setName('doModelEdit')->add(Authenticate::class);

$app->get(
    '/model/remove/{id:\d+}',
    [ModelsController::class, 'confirmDelete']
)->setName('removeModel')->add(Authenticate::class);

$app->post(
    '/models/remove',
    [ModelsController::class, 'confirmDelete']
)->setName('removeModels')->add(Authenticate::class);

$app->post(
    '/model/remove[/{id:\d+}]',
    [ModelsController::class, 'delete']
)->setName('doRemoveModel')->add(Authenticate::class);

$app->get(
    '/brands[/{option:page|order}/{value:\d+}]',
    [PropertiesController::class, 'brandsList']
)->setName('brandsList')->add(Authenticate::class);

$app->get(
    '/colors[/{option:page|order}/{value:\d+}]',
    [PropertiesController::class, 'colorsList']
)->setName('colorsList')->add(Authenticate::class);

$app->get(
    '/states[/{option:page|order}/{value:\d+}]',
    [PropertiesController::class, 'statesList']
)->setName('statesList')->add(Authenticate::class);

$app->get(
    '/finitions[/{option:page|order}/{value:\d+}]',
    [PropertiesController::class, 'finitionsList']
)->setName('finitionsList')->add(Authenticate::class);

$app->get(
    '/bodies[/{option:page|order}/{value:\d+}]',
    [PropertiesController::class, 'bodiesList']
)->setName('bodiesList')->add(Authenticate::class);

$app->get(
    '/transmissions[/{option:page|order}/{value:\d+}]',
    [PropertiesController::class, 'transmissionsList']
)->setName('transmissionsList')->add(Authenticate::class);

$app->post(
    '/{property:brand|color|state|finition|body|transmission}/filter',
    [PropertiesController::class, 'filter']
)->setName('propertyFilter')->add(Authenticate::class);

$app->get(
    '/{property:brand|color|state|finition|body|transmission}/add',
    [PropertiesController::class, 'propertyAdd']
)->setName('propertyAdd')->add(Authenticate::class);

$app->get(
    '/{property:brand|color|state|finition|body|transmission}/edit/{id:\d+}',
    [PropertiesController::class, 'propertyEdit']
)->setName('propertyEdit')->add(Authenticate::class);

$app->post(
    '/{property:brand|color|state|finition|body|transmission}/add',
    [PropertiesController::class, 'doPropertyAdd']
)->setName('doPropertyAdd')->add(Authenticate::class);

$app->post(
    '/{property:brand|color|state|finition|body|transmission}/edit/{id:\d+}',
    [PropertiesController::class, 'doPropertyEdit']
)->setName('doPropertyEdit')->add(Authenticate::class);

$app->get(
    '/{property:brand}/show/{id:\d+}',
    [PropertiesController::class, 'propertyShow']
)->setName('propertyShow')->add(Authenticate::class);

$app->get(
    '/{property:brand|color|state|finition|body|transmission}/remove/{id:\d+}',
    [PropertiesController::class, 'removeProperty']
)->setName('removeProperty')->add(Authenticate::class);

$app->get(
    '/{property:brand|color|state|finition|body|transmission}' . '/remove',
    [PropertiesController::class, 'removeProperties']
)->setName('removeProperties')->add(Authenticate::class);

$app->post(
    '/{property:brand|color|state|finition|body|transmission}/remove[/{id:\d+}]',
    [PropertiesController::class, 'doRemoveProperty']
)->setName('doRemoveProperty')->add(Authenticate::class);
