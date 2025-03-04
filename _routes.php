<?php

/**
 * Copyright © 2003-2025 The Galette Team
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
)->setName('vehiclesList')->add($authenticate);

$app->post(
    '/vehicle/filter',
    [Controller::class, 'filter']
)->setName('vehiclesFilter')->add($authenticate);

$app->get(
    '/member/{id:\d+}/vehicles[/{option:page|order}/{value:\d+}]',
    [Controller::class, 'memberVehiclesList']
)->setName('memberVehiclesList')->add($authenticate);

$app->group('/public', function () use ($app): void {
    $app->get(
        '/public/vehicles',
        [Controller::class, 'publicVehiclesList']
    )->setName('publicVehiclesList');
})->add(\Galette\Middleware\PublicPages::class);

$app->get(
    '/my-vehicles',
    [Controller::class, 'myVehiclesList']
)->setName('myVehiclesList')->add($authenticate);

$app->get(
    '/vehicle/add',
    [Controller::class, 'showAddVehicle']
)->setName('vehicleAdd')->add($authenticate);

$app->get(
    '/vehicle/edit/{id:\d+}',
    [Controller::class, 'showEditVehicle']
)->setName('vehicleEdit')->add($authenticate);

$app->post(
    '/vehicle/add',
    [Controller::class, 'doAddVehicle']
)->setName('doVehicleAdd')->add($authenticate);

$app->post(
    '/vehicle/edit/{id:\d+}',
    [Controller::class, 'doEditVehicle']
)->setName('doVehicleEdit')->add($authenticate);

$app->get(
    '/vehicle/history/{id:\d+}',
    [Controller::class, 'vehicleHistory']
)->setName('vehicleHistory')->add($authenticate);

$app->post(
    '/ajax/models',
    [Controller::class, 'ajaxModels']
)->setName('ajaxModels')->add($authenticate);

$app->get(
    '/vehicle/remove/{id:\d+}',
    [Controller::class, 'removeVehicle']
)->setName('removeVehicle')->add($authenticate);

$app->map(
    ['GET', 'POST'],
    '/vehicles/remove',
    [Controller::class, 'removeVehicles']
)->setName('removeVehicles')->add($authenticate);

$app->post(
    '/vehicle/remove[/{id:\d+}]',
    [Controller::class, 'doRemoveVehicle']
)->setName('doRemoveVehicle')->add($authenticate);

//Batch actions on vehicles list
$app->post(
    '/vehicles/batch',
    function ($request, $response) use ($container) {
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
)->setName('batch-vehicleslist')->add($authenticate);

$app->get(
    '/models[/{option:page|order}/{value:\d+}]',
    [ModelsController::class, 'list']
)->setName('modelsList')->add($authenticate);

$app->post(
    '/models/filter',
    [ModelsController::class, 'filter']
)->setName('modelsFilter')->add($authenticate);

$app->get(
    '/models/add',
    [ModelsController::class, 'add']
)->setName('modelAdd')->add($authenticate);

$app->get(
    '/models/edit/{id:\d+}',
    [ModelsController::class, 'edit']
)->setName('modelEdit')->add($authenticate);

$app->post(
    '/models/add',
    [ModelsController::class, 'doAdd']
)->setName('doModelAdd')->add($authenticate);

$app->post(
    '/models/edit/{id:\d+}',
    [ModelsController::class, 'doEdit']
)->setName('doModelEdit')->add($authenticate);

$app->get(
    '/model/remove/{id:\d+}',
    [ModelsController::class, 'confirmDelete']
)->setName('removeModel')->add($authenticate);

$app->post(
    '/models/remove',
    [ModelsController::class, 'confirmDelete']
)->setName('removeModels')->add($authenticate);

$app->post(
    '/model/remove[/{id:\d+}]',
    [ModelsController::class, 'delete']
)->setName('doRemoveModel')->add($authenticate);

$app->get(
    '/brands[/{option:page|order}/{value:\d+}]',
    [PropertiesController::class, 'brandsList']
)->setName('brandsList')->add($authenticate);

$app->get(
    '/colors[/{option:page|order}/{value:\d+}]',
    [PropertiesController::class, 'colorsList']
)->setName('colorsList')->add($authenticate);

$app->get(
    '/states[/{option:page|order}/{value:\d+}]',
    [PropertiesController::class, 'statesList']
)->setName('statesList')->add($authenticate);

$app->get(
    '/finitions[/{option:page|order}/{value:\d+}]',
    [PropertiesController::class, 'finitionsList']
)->setName('finitionsList')->add($authenticate);

$app->get(
    '/bodies[/{option:page|order}/{value:\d+}]',
    [PropertiesController::class, 'bodiesList']
)->setName('bodiesList')->add($authenticate);

$app->get(
    '/transmissions[/{option:page|order}/{value:\d+}]',
    [PropertiesController::class, 'transmissionsList']
)->setName('transmissionsList')->add($authenticate);

$app->post(
    '/{property:brand|color|state|finition|body|transmission}/filter',
    [PropertiesController::class, 'filter']
)->setName('propertyFilter')->add($authenticate);

$app->get(
    '/{property:brand|color|state|finition|body|transmission}/add',
    [PropertiesController::class, 'propertyAdd']
)->setName('propertyAdd')->add($authenticate);

$app->get(
    '/{property:brand|color|state|finition|body|transmission}/edit/{id:\d+}',
    [PropertiesController::class, 'propertyEdit']
)->setName('propertyEdit')->add($authenticate);

$app->post(
    '/{property:brand|color|state|finition|body|transmission}/add',
    [PropertiesController::class, 'doPropertyAdd']
)->setName('doPropertyAdd')->add($authenticate);

$app->post(
    '/{property:brand|color|state|finition|body|transmission}/edit/{id:\d+}',
    [PropertiesController::class, 'doPropertyEdit']
)->setName('doPropertyEdit')->add($authenticate);

$app->get(
    '/{property:brand}/show/{id:\d+}',
    [PropertiesController::class, 'propertyShow']
)->setName('propertyShow')->add($authenticate);

$app->get(
    '/{property:brand|color|state|finition|body|transmission}/remove/{id:\d+}',
    [PropertiesController::class, 'removeProperty']
)->setName('removeProperty')->add($authenticate);

$app->get(
    '/{property:brand|color|state|finition|body|transmission}' . '/remove',
    [PropertiesController::class, 'removeProperties']
)->setName('removeProperties')->add($authenticate);

$app->post(
    '/{property:brand|color|state|finition|body|transmission}/remove[/{id:\d+}]',
    [PropertiesController::class, 'doRemoveProperty']
)->setName('doRemoveProperty')->add($authenticate);
