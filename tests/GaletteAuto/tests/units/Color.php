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

use Galette\Tests\GaletteTestCase;

/**
 * Color tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Color extends GaletteTestCase
{
    protected int $seed = 20240130141727;

    /**
     * Cleanup after each test method
     *
     * @return void
     */
    public function tearDown(): void
    {
        $delete = $this->zdb->delete(AUTO_PREFIX . \GaletteAuto\Color::TABLE);
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
        $color = new \GaletteAuto\Color($this->zdb);
        $this->assertSame('Color', $color->getFieldLabel());

        $this->assertCount(0, $color->getList());
        $this->assertSame('0 color', $color->displayCount());
    }

    /**
     * Test add and update
     *
     * @return void
     */
    public function testCrud(): void
    {
        $color = new \GaletteAuto\Color($this->zdb);
        //ensure the table is empty
        $this->assertCount(0, $color->getList());

        //Add new color
        $color->value = 'Red';
        $this->assertTrue($color->store(true));
        $first_id = $color->id;

        $this->assertCount(1, $color->getList());
        $listed_color = $color->getList()[0];
        $this->assertInstanceOf(\ArrayObject::class, $listed_color);
        $this->assertGreaterThan(0, $listed_color->id_color);
        $this->assertSame('Red', $listed_color->color);
        $this->assertSame('1 color', $color->displayCount());

        //add another one
        $color = new \GaletteAuto\Color($this->zdb);
        $color->value = 'Blu';
        $this->assertTrue($color->store(true));
        $id = $color->id;

        $this->assertCount(2, $color->getList());
        $this->assertSame('2 colors', $color->displayCount());

        $color = new \GaletteAuto\Color($this->zdb);
        $this->assertTrue($color->load($id));
        $color->value = 'Blue';
        $this->assertTrue($color->store());

        $this->assertCount(2, $color->getList());
        $this->assertSame('2 colors', $color->displayCount());

        $color = new \GaletteAuto\Color($this->zdb);
        $this->assertTrue($color->delete([$first_id]));
        $list = $color->getList();
        $this->assertCount(1, $list);
        $last_color = $list[0];
        $this->assertSame($id, (int)$last_color->id_color);
    }

    /**
     * Test load error
     *
     * @return void
     */
    public function testLoadError(): void
    {
        $color = new \GaletteAuto\Color($this->zdb);
        $this->expectNoLogEntry();
        $this->assertFalse($color->load(999));
        $this->expectLogEntry(
            \Analog::ERROR,
            '[GaletteAuto\Color] Cannot load colors from id `999`'
        );
    }

    /**
     * Test getClassName
     *
     * @return void
     */
    public function testGetClassName(): void
    {
        $this->assertSame('\\' . \GaletteAuto\Color::class, \GaletteAuto\Color::getClassForPropName('color'));
    }
}
