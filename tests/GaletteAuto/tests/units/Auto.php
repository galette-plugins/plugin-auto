<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\tests\units;

use Galette\Tests\GaletteTestCase;

/**
 * Model tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Auto extends GaletteTestCase
{
    protected int $seed = 20240212212207;
    protected bool $load_plugins = true;

    protected \Galette\Core\Plugins $plugins;

    /**
     * Set up tests
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->plugins = $this->container->get(\Galette\Core\Plugins::class);
    }

    /**
     * Test add and update
     */
    public function testCrud(): void
    {
        $body = new \GaletteAuto\Body($this->zdb);
        $body->setValue('Berline');
        $body->store(new: true);
        $body_id = $body->getId();

        $color = new \GaletteAuto\Color($this->zdb);
        $color->setValue('Grey');
        $color->store(new: true);
        $color_id = $color->getId();

        $finition = new \GaletteAuto\Finition($this->zdb);
        $finition->setValue('Standard');
        $finition->store(new: true);
        $finition_id = $finition->getId();

        $state = new \GaletteAuto\State($this->zdb);
        $state->setValue('Correct');
        $state->store(new: true);
        $state_id = $state->getId();

        $transmission = new \GaletteAuto\Transmission($this->zdb);
        $transmission->setValue('Manual');
        $transmission->store(new: true);
        $transmission_id = $transmission->getId();

        $type = new \GaletteAuto\VehicleType($this->zdb);
        $type->setValue('Car');
        $type->setFields([
            'name' => \GaletteAuto\VehicleType::REQUIRED,
            'registration' => \GaletteAuto\VehicleType::REQUIRED,
            'fuel' => \GaletteAuto\VehicleType::REQUIRED,
            'first_registration_date' => \GaletteAuto\VehicleType::REQUIRED,
            'first_circulation_date' => \GaletteAuto\VehicleType::REQUIRED,
            'color' => \GaletteAuto\VehicleType::REQUIRED,
            'state' => \GaletteAuto\VehicleType::REQUIRED,
            'body' => \GaletteAuto\VehicleType::REQUIRED,
            'transmission' => \GaletteAuto\VehicleType::REQUIRED,
            'finition' => \GaletteAuto\VehicleType::REQUIRED,
        ]);
        $type->store(new: true);
        $type_id = $type->getId();

        $brand = new \GaletteAuto\Brand($this->zdb);
        $brand->setValue('Peugeot');
        $brand->store(new: true);
        $brand_id = $brand->getId();

        $model = new \GaletteAuto\Model($this->zdb);
        $data = [
            'model' => '307',
            'brand' => $brand_id,
        ];
        $this->assertTrue($model->check($data));
        $model->store(new: true);
        $model_id = $model->getId();

        $this->logSuperAdmin();
        $access = new \GaletteAuto\VehicleAccess($this->zdb, $this->login, $this->preferences);
        $vehicles = new \GaletteAuto\Repository\Vehicles(
            plugins: $this->plugins,
            zdb: $this->zdb,
            login: $this->login,
            history: $this->history
        );
        $auto = new \GaletteAuto\Auto($this->plugins, $this->zdb);

        $data = [];
        $this->assertFalse($auto->check($data, $access));
        $this->assertSame(
            [
                '- Mandatory field <a href="#type">vehicle type</a> empty.',
                '- Mandatory field <a href="#model">model</a> empty.',
                '- you must attach an owner to this car',
            ],
            $auto->getErrors()
        );

        //set some required values; the type brings its own
        $data = [
            'color' => $color_id,
            'body' => $body_id,
            'finition' => $finition_id,
            'state' => $state_id,
            'transmission' => $transmission_id,
            'type' => $type_id,
        ];
        $this->assertFalse($auto->check($data, $access));
        $this->assertSame(
            [
                '- Mandatory field <a href="#registration">registration number</a> empty.',
                '- Mandatory field <a href="#name">name</a> empty.',
                '- Mandatory field <a href="#first_registration_date">first registration date</a> empty.',
                '- Mandatory field <a href="#first_circulation_date">first circulation date</a> empty.',
                '- Mandatory field <a href="#fuel">fuel</a> empty.',
                '- Mandatory field <a href="#model">model</a> empty.',
                '- you must attach an owner to this car',
            ],
            $auto->getErrors()
        );

        //create vehicle
        $adh = $this->getMemberOne();
        $data = [
            'registration' => 'GA-123-TE',
            'name' => 'Titine',
            'first_registration_date' => '2001-02-12',
            'first_circulation_date' => '2001-02-13',
            'fuel' => \GaletteAuto\Auto::FUEL_DIESEL,
            'model' => $model_id,
            'color' => $color_id,
            'body' => $body_id,
            'finition' => $finition_id,
            'state' => $state_id,
            'transmission' => $transmission_id,
            'type' => $type_id,
            'owner_id' => $adh->id,
        ];
        $check = $auto->check($data, $access);
        $this->assertSame([], $auto->getErrors());
        $this->assertTrue($check);

        $vehicles->store($auto);
        $auto_id = $auto->getId();

        //check history
        $history = new \GaletteAuto\History($this->zdb);
        $this->assertTrue($history->load($auto->getId()));
        $this->assertCount(1, $history->getEntries());

        $entry = $history->getEntries()[0];
        $this->assertSame(
            [
                'id_car',
                'id_adh',
                'history_date',
                'car_registration',
                'id_color',
                'id_state',
                'color',
                'state',
                'formatted_date',
                'owner'
            ],
            array_keys($entry)
        );

        $this->assertSame($auto->getId(), (int)$entry['id_car']);
        $this->assertSame($adh->id, (int)$entry['id_adh']);
        $this->assertSame('GA-123-TE', $entry['car_registration']);
        $this->assertSame('Grey', $entry['color']);
        $this->assertSame('Correct', $entry['state']);

        //update car - change owner and color
        $auto = new \GaletteAuto\Auto($this->plugins, $this->zdb);
        $this->assertTrue($auto->load($auto_id));

        $adh2 = $this->getMemberTwo();
        $color2 = new \GaletteAuto\Color($this->zdb);
        $color2->setValue('Yellow');
        $color2->store(new: true);
        $color2_id = $color2->getId();

        $data = [
            'registration' => 'GA-123-TE',
            'name' => 'Titine',
            'first_registration_date' => '2001-02-12',
            'first_circulation_date' => '2001-02-13',
            'fuel' => \GaletteAuto\Auto::FUEL_DIESEL,
            'model' => $model_id,
            'color' => $color2_id,
            'body' => $body_id,
            'finition' => $finition_id,
            'state' => $state_id,
            'transmission' => $transmission_id,
            'type' => $type_id,
            'owner_id' => $adh2->id,
            'change_owner' => true,
        ];
        $check = $auto->check($data, $access);
        $this->assertSame([], $auto->getErrors());
        $this->assertTrue($check);

        //history is sorted on a date with seconds: entries of the same second have no order
        sleep(1);
        $vehicles->store($auto);

        //check history
        $history = new \GaletteAuto\History($this->zdb);
        $this->assertTrue($history->load($auto->getId()));
        $this->assertCount(2, $history->getEntries());

        $entry = $history->getEntries()[1];
        $this->assertSame($auto->getId(), (int)$entry['id_car']);
        $this->assertSame($adh2->id, (int)$entry['id_adh']);
        $this->assertSame('GA-123-TE', $entry['car_registration']);
        $this->assertSame('Yellow', $entry['color']);
        $this->assertSame('Correct', $entry['state']);

        //add another car
        $auto = new \GaletteAuto\Auto($this->plugins, $this->zdb);
        $data = [
            'registration' => 'AS-123-SO',
            'name' => 'My car',
            'first_registration_date' => '2015-03-14',
            'first_circulation_date' => '2015-03-15',
            'fuel' => \GaletteAuto\Auto::FUEL_DIESEL,
            'model' => $model_id,
            'color' => $color_id,
            'body' => $body_id,
            'finition' => $finition_id,
            'state' => $state_id,
            'transmission' => $transmission_id,
            'type' => $type_id,
            'owner_id' => $adh->id,
        ];
        $check = $auto->check($data, $access);
        $this->assertSame([], $auto->getErrors());
        $this->assertTrue($check);

        $vehicles->store($auto);
        $auto2_id = $auto->getId();

        $this->assertTrue($history->load($auto2_id));
        $this->assertCount(1, $history->getEntries());

        //sorted by name
        $this->assertSame(
            ['My car', 'Titine'],
            array_map(fn($car) => $car->getName(), $vehicles->getList())
        );
        $this->assertSame(2, $vehicles->getCount());

        $vehicles->remove([$auto_id]);
        $this->assertCount(1, $vehicles->getList());
        $this->expectNoLogEntry();
        $this->assertFalse($auto->load($auto_id));
        $this->expectLogEntry(
            \Analog\Analog::ERROR,
            '[GaletteAuto\Auto] Cannot load vehicle #' . $auto_id . ' | Vehicle not found'
        );
    }

    /**
     * Test a vehicle without any of the optional properties
     */
    public function testOptionalProperties(): void
    {
        $type = new \GaletteAuto\VehicleType($this->zdb);
        $type->setValue('Moped');
        $type->store(new: true);

        $brand = new \GaletteAuto\Brand($this->zdb);
        $brand->setValue('Velosolex');
        $brand->store(new: true);

        $model = new \GaletteAuto\Model($this->zdb);
        $this->assertTrue($model->check(['model' => '3800', 'brand' => $brand->getId()]));
        $model->store(new: true);

        $adh = $this->getMemberOne();
        $insert = $this->zdb->insert(AUTO_PREFIX . \GaletteAuto\Auto::TABLE);
        $insert->values([
            'car_name' => 'Solex',
            'car_registration' => '',
            'car_creation_date' => date('Y-m-d'),
            \GaletteAuto\Model::PK => $model->getId(),
            \GaletteAuto\VehicleType::PK => $type->getId(),
            \Galette\Entity\Adherent::PK => $adh->id,
        ]);
        $this->zdb->execute($insert);

        $vehicles = new \GaletteAuto\Repository\Vehicles(
            plugins: $this->plugins,
            zdb: $this->zdb,
            login: $this->login,
            history: $this->history
        );
        $list = $vehicles->getList(id_adh: $adh->id);
        $this->assertCount(1, $list);
        $auto = $list[0];
        $this->assertSame('Solex', $auto->getName());
        $this->assertSame('3800', $auto->getModel()->getModel());
        $this->assertSame('Moped', $auto->getType()->getValue());
        $this->assertNull($auto->getFirstRegistrationDate());
        $this->assertNull($auto->getFirstCirculationDate());
        $this->assertNull($auto->getFirstCirculationYear());
        foreach (['getColor', 'getState', 'getBody', 'getTransmission', 'getFinition'] as $getter) {
            $this->assertNull($auto->$getter()->getId(), $getter);
            $this->assertNull($auto->$getter()->getValue(), $getter);
        }

        //same from a single load
        $loaded = new \GaletteAuto\Auto($this->plugins, $this->zdb);
        $this->assertTrue($loaded->load((int)$auto->getId()));
        $this->assertNull($loaded->getColor()->getId());
        $this->assertNull($loaded->getFirstRegistrationDate());

        //history keeps an entry without color nor state
        $history = $loaded->getHistory();
        $history->register($loaded->getHistoryValues());
        $this->assertTrue($history->load((int)$loaded->getId()));
        $entries = $history->getEntries();
        $this->assertCount(1, $entries);
        $this->assertNull($entries[0][\GaletteAuto\Color::FIELD]);
        $this->assertNull($entries[0][\GaletteAuto\State::FIELD]);
    }

    /**
     * Test the fields modes of vehicle types
     */
    public function testTypeFields(): void
    {
        $this->logSuperAdmin();
        $access = new \GaletteAuto\VehicleAccess($this->zdb, $this->login, $this->preferences);
        $vehicles = new \GaletteAuto\Repository\Vehicles(
            plugins: $this->plugins,
            zdb: $this->zdb,
            login: $this->login,
            history: $this->history
        );

        $moped = new \GaletteAuto\VehicleType($this->zdb);
        $moped->setValue('Moped');
        $moped->setFields([
            'registration' => \GaletteAuto\VehicleType::HIDDEN,
            'body' => \GaletteAuto\VehicleType::HIDDEN,
            'transmission' => \GaletteAuto\VehicleType::HIDDEN,
            'finition' => \GaletteAuto\VehicleType::HIDDEN,
            'seats' => \GaletteAuto\VehicleType::HIDDEN,
            'name' => \GaletteAuto\VehicleType::REQUIRED,
        ]);
        $moped->store(new: true);
        $car = new \GaletteAuto\VehicleType($this->zdb);
        $car->setValue('Car');
        $car->store(new: true);

        $body = new \GaletteAuto\Body($this->zdb);
        $body->setValue('Berline');
        $body->store(new: true);
        $brand = new \GaletteAuto\Brand($this->zdb);
        $brand->setValue('Velosolex');
        $brand->store(new: true);
        $model = new \GaletteAuto\Model($this->zdb);
        $this->assertTrue($model->check(['model' => '3800', 'brand' => $brand->getId()]));
        $model->store(new: true);

        $adh = $this->getMemberOne();
        $auto = new \GaletteAuto\Auto($this->plugins, $this->zdb);
        $data = [
            'type' => $moped->getId(),
            'model' => $model->getId(),
            'owner_id' => $adh->id,
            'first_registration_date' => '',
            'color' => '-1',
        ];
        //name is required by the type
        $this->assertFalse($auto->check($data, $access));
        $this->assertSame(['- Mandatory field <a href="#name">name</a> empty.'], $auto->getErrors());

        //hidden fields are ignored, optional ones may be empty
        $data['name'] = 'Solex';
        $data['registration'] = 'ignored';
        $data['body'] = $body->getId();
        $data['seats'] = 'not a number';
        $this->assertTrue($auto->check($data, $access), implode(', ', $auto->getErrors()));
        $vehicles->store($auto);

        $select = $this->zdb->select(AUTO_PREFIX . \GaletteAuto\Auto::TABLE);
        $select->where([\GaletteAuto\Auto::PK => $auto->getId()]);
        $row = $this->zdb->execute($select)->current();
        $this->assertSame('Solex', $row['car_name']);
        $this->assertSame('', $row['car_registration']);
        foreach (['car_first_registration_date', 'car_seats', 'id_color', 'id_body', 'id_state'] as $column) {
            $this->assertNull($row[$column], $column);
        }

        //as a car, the body can be set
        $auto = new \GaletteAuto\Auto($this->plugins, $this->zdb);
        $this->assertTrue($auto->load((int)$row[\GaletteAuto\Auto::PK]));
        $data['type'] = $car->getId();
        $data['registration'] = 'SO-123-LX';
        $this->assertTrue($auto->check(array_merge($data, ['seats' => '1']), $access));
        $this->assertSame((int)$body->getId(), $auto->getBody()->getId());
        $this->assertSame('SO-123-LX', $auto->getRegistration());
        $this->assertSame(1, $auto->getSeats());

        //back to a moped: hidden fields values are dropped
        $data['type'] = $moped->getId();
        $this->assertTrue($auto->check($data, $access));
        $this->assertNull($auto->getBody()->getId());
        $this->assertSame('', $auto->getRegistration());
        $this->assertNull($auto->getSeats());
    }

    /**
     * Test fuels
     */
    public function testListFuels(): void
    {
        $auto = new \GaletteAuto\Auto($this->plugins, $this->zdb);
        $this->assertCount(6, $auto->listFuels());
    }

    /**
     * Test load error
     */
    public function testLoadError(): void
    {
        $auto = new \GaletteAuto\Auto($this->plugins, $this->zdb);
        $this->expectNoLogEntry();
        $this->assertFalse($auto->load(999));
        $this->expectLogEntry(
            \Analog\Analog::ERROR,
            '[GaletteAuto\Auto] Cannot load vehicle #999 | Vehicle not found'
        );
    }
}
