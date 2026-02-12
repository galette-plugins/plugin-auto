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
        $body->value = 'Berline';
        $this->assertTrue($body->store(true));
        $body_id = $body->id;

        $color = new \GaletteAuto\Color($this->zdb);
        $color->value = 'Grey';
        $this->assertTrue($color->store(true));
        $color_id = $color->id;

        $finition = new \GaletteAuto\Finition($this->zdb);
        $finition->value = 'Standard';
        $this->assertTrue($finition->store(true));
        $finition_id = $finition->id;

        $state = new \GaletteAuto\State($this->zdb);
        $state->value = 'Correct';
        $this->assertTrue($state->store(true));
        $state_id = $state->id;

        $transmission = new \GaletteAuto\Transmission($this->zdb);
        $transmission->value = 'Manual';
        $this->assertTrue($transmission->store(true));
        $transmission_id = $transmission->id;

        $brand = new \GaletteAuto\Brand($this->zdb);
        $brand->value = 'Peugeot';
        $this->assertTrue($brand->store(true));
        $brand_id = $brand->id;

        $model = new \GaletteAuto\Model($this->zdb);
        $data = [
            'model' => '307',
            'brand' => $brand_id,
        ];
        $this->assertTrue($model->check($data));
        $this->assertTrue($model->store(true));
        $model_id = $model->id;

        $auto = new \GaletteAuto\Auto($this->plugins, $this->zdb);

        $data = [];
        $this->assertFalse($auto->check($data));
        $this->assertSame(
            [
                '- Mandatory field <a href="#registration">registration</a> empty.',
                '- Mandatory field <a href="#name">name</a> empty.',
                '- Mandatory field <a href="#first_registration_date">first registration date</a> empty.',
                '- Mandatory field <a href="#first_circulation_date">first circulation date</a> empty.',
                '- Mandatory field <a href="#fuel">fuel</a> empty.',
                '- Mandatory field <a href="#finition">finition</a> empty.',
                '- Mandatory field <a href="#color">color</a> empty.',
                '- Mandatory field <a href="#model">model</a> empty.',
                '- Mandatory field <a href="#transmission">transmission</a> empty.',
                '- Mandatory field <a href="#body">body</a> empty.',
                '- Mandatory field <a href="#state">state</a> empty.',
                '- you must attach an owner to this car',
            ],
            $auto->getErrors()
        );

        //set some required values
        $data = [
            'color' => $color_id,
            'body' => $body_id,
            'finition' => $finition_id,
            'state' => $state_id,
            'transmission' => $transmission_id,
        ];
        $this->assertFalse($auto->check($data));
        $this->assertSame(
            [
                '- Mandatory field <a href="#registration">registration</a> empty.',
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
            'owner_id' => $adh->id,
        ];
        $check = $auto->check($data);
        $this->assertSame([], $auto->getErrors());
        $this->assertTrue($check);

        $stored = $auto->store(true);
        $this->assertEquals([], $auto->getErrors());
        $this->assertTrue($stored);
        $auto_id = $auto->id;

        //check history
        $history = new \GaletteAuto\History($this->zdb);
        $this->assertTrue($history->load($auto->id));
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
                'formatted_date',
                'owner',
                'color',
                'state'
            ],
            array_keys($entry)
        );

        $this->assertSame($auto->id, (int)$entry['id_car']);
        $this->assertSame($adh->id, (int)$entry['id_adh']);
        $this->assertSame('GA-123-TE', $entry['car_registration']);
        $this->assertSame('Grey', $entry['color']);
        $this->assertSame('Correct', $entry['state']);

        //update car - change owner and color
        $auto = new \GaletteAuto\Auto($this->plugins, $this->zdb);
        $this->assertTrue($auto->load($auto_id));

        $adh2 = $this->getMemberTwo();
        $color2 = new \GaletteAuto\Color($this->zdb);
        $color2->value = 'Yellow';
        $this->assertTrue($color2->store(true));
        $color2_id = $color2->id;

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
            'owner_id' => $adh2->id,
            'change_owner' => true,
        ];
        $check = $auto->check($data);
        $this->assertSame([], $auto->getErrors());
        $this->assertTrue($check);

        $stored = $auto->store();
        $this->assertEquals([], $auto->getErrors());
        $this->assertTrue($stored);

        //check history
        $history = new \GaletteAuto\History($this->zdb);
        $this->assertTrue($history->load($auto->id));
        $this->assertCount(2, $history->getEntries());

        $entry = $history->getEntries()[1];
        $this->assertSame($auto->id, (int)$entry['id_car']);
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
            'owner_id' => $adh->id,
        ];
        $check = $auto->check($data);
        $this->assertSame([], $auto->getErrors());
        $this->assertTrue($check);

        $stored = $auto->store(true);
        $this->assertEquals([], $auto->getErrors());
        $this->assertTrue($stored);
        $auto2_id = $auto->id;

        $this->assertTrue($history->load($auto2_id));
        $this->assertCount(1, $history->getEntries());

        $this->logSuperAdmin();
        $autos = new \GaletteAuto\Autos($this->plugins, $this->zdb);
        $this->assertCount(2, $autos->getList());

        $this->assertTrue($autos->removeVehicles([$auto_id]));
        $this->assertCount(1, $autos->getList());
        $this->expectNoLogEntry();
        $this->assertFalse($auto->load($auto_id));
        $this->expectLogEntry(
            \Analog::ERROR,
            '[GaletteAuto\Auto] Cannot load car from id `' . $auto_id . '` | Vehicle not found'
        );
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
            \Analog::ERROR,
            '[GaletteAuto\Auto] Cannot load car from id `999` | Vehicle not found'
        );
    }
}
