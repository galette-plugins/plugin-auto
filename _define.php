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

$this->register(
    'Galette Auto',                         //Name
    'Plugin to manage Automobile clubs',    //Short description
    'Johan Cwiklinski',                     //Author
    '2.1.3',                                //Version
    '1.2.0',                                //Galette compatible version
    'auto',                                 //routing name
    '2024-10-21',                           //Release date
    [ //routes permissions
        'vehiclesList'      => 'groupmanager',
        'memberVehiclesList' => 'groupmanager',
        'myVehiclesList'    => 'member',
        'vehiclesFilter'    => 'member',
        'vehicleAdd'        => 'member',
        'vehicleEdit'       => 'member',
        'ajaxModels'        => 'member',
        'doVehicleAdd'      => 'member',
        'doVehicleEdit'     => 'member',
        'batch-vehicleslist' => 'member',
        'removeVehicle'     => 'member',
        'removeVehicles'    => 'member',
        'doRemoveVehicle'   => 'member',
        'vehicleHistory'    => 'member',
        'modelsList'        => 'groupmanager',
        'modelsFilter'      => 'groupmanager',
        'modelEdit'         => 'groupmanager',
        'modelAdd'          => 'groupmanager',
        'doModelEdit'       => 'groupmanager',
        'doModelAdd'        => 'groupmanager',
        'removeModel'       => 'staff',
        'removeModels'      => 'staff',
        'doRemoveModel'     => 'staff',
        'batch-modelslist'  => 'staff',
        'brandsList'        => 'staff',
        'colorsList'        => 'staff',
        'statesList'        => 'staff',
        'finitionsList'     => 'staff',
        'bodiesList'        => 'staff',
        'transmissionsList' => 'staff',
        'propertyAdd'       => 'staff',
        'propertyEdit'      => 'staff',
        'doPropertyAdd'     => 'staff',
        'doPropertyEdit'    => 'staff',
        'propertyShow'      => 'staff',
        'propertyFilter'    => 'staff',
        'batch-propertieslist' => 'staff',
        'removeProperty'    => 'staff',
        'removeProperties'  => 'staff',
        'doRemoveProperty'  => 'staff'
    ]
);
