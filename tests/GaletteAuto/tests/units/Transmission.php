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

namespace GaletteAuto\tests\units;

use Galette\GaletteTestCase;

/**
 * Transmission tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Transmission extends GaletteTestCase
{
    protected int $seed = 20240130141727;

    /**
     * Cleanup after each test method
     *
     * @return void
     */
    public function tearDown(): void
    {
        $delete = $this->zdb->delete(AUTO_PREFIX . \GaletteAuto\Transmission::TABLE);
        $this->zdb->execute($delete);
        parent::tearDown();
    }

    /**
     * Test empty
     *
     * @return void
     */
    public function testEmpty(): void
    {
        $transmission = new \GaletteAuto\Transmission($this->zdb);
        $this->assertSame('Transmission', $transmission->getFieldLabel());

        $this->assertCount(0, $transmission->getList());
        $this->assertSame('0 transmission', $transmission->displayCount());
    }

    /**
     * Test add and update
     *
     * @return void
     */
    public function testCrud(): void
    {
        $transmission = new \GaletteAuto\Transmission($this->zdb);
        //ensure the table is empty
        $this->assertCount(0, $transmission->getList());

        //Add new transmission
        $transmission->value = 'Manual';
        $this->assertTrue($transmission->store(true));
        $first_id = $transmission->id;

        $this->assertCount(1, $transmission->getList());
        $listed_transmission = $transmission->getList()[0];
        $this->assertInstanceOf(\ArrayObject::class, $listed_transmission);
        $this->assertGreaterThan(0, $listed_transmission->id_transmission);
        $this->assertSame('Manual', $listed_transmission->transmission);
        $this->assertSame('1 transmission', $transmission->displayCount());

        //add another one
        $transmission = new \GaletteAuto\Transmission($this->zdb);
        $transmission->value = 'Auto';
        $this->assertTrue($transmission->store(true));
        $id = $transmission->id;

        $this->assertCount(2, $transmission->getList());
        $this->assertSame('2 transmissions', $transmission->displayCount());

        $transmission = new \GaletteAuto\Transmission($this->zdb);
        $this->assertTrue($transmission->load($id));
        $transmission->value = 'Automatic';
        $this->assertTrue($transmission->store());

        $this->assertCount(2, $transmission->getList());
        $this->assertSame('2 transmissions', $transmission->displayCount());

        $transmission = new \GaletteAuto\Transmission($this->zdb);
        $this->assertTrue($transmission->delete([$first_id]));
        $list = $transmission->getList();
        $this->assertCount(1, $list);
        $last_transmission = $list[0];
        $this->assertSame($id, (int)$last_transmission->id_transmission);
    }

    /**
     * Test load error
     *
     * @return void
     */
    public function testLoadError(): void
    {
        $transmission = new \GaletteAuto\Transmission($this->zdb);
        $this->expectNoLogEntry();
        $this->assertFalse($transmission->load(999));
        $this->expectLogEntry(
            \Analog::ERROR,
            '[GaletteAuto\Transmission] Cannot load transmissions from id `999`'
        );
    }

    /**
     * Test getClassName
     *
     * @return void
     */
    public function testGetClassName(): void
    {
        $this->assertSame('\\' . \GaletteAuto\Transmission::class, \GaletteAuto\Transmission::getClassForPropName('transmission'));
    }
}
