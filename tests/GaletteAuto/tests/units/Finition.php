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
 * Finition tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Finition extends GaletteTestCase
{
    protected int $seed = 20240130141727;

    /**
     * Cleanup after each test method
     *
     * @return void
     */
    public function tearDown(): void
    {
        $delete = $this->zdb->delete(AUTO_PREFIX . \GaletteAuto\Finition::TABLE);
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
        $finition = new \GaletteAuto\Finition($this->zdb);
        $this->assertSame('Finition', $finition->getFieldLabel());

        $this->assertCount(0, $finition->getList());
        $this->assertSame('0 finition', $finition->displayCount());
    }

    /**
     * Test add and update
     *
     * @return void
     */
    public function testCrud(): void
    {
        $finition = new \GaletteAuto\Finition($this->zdb);
        //ensure the table is empty
        $this->assertCount(0, $finition->getList());

        //Add new finition
        $finition->value = 'Feline';
        $this->assertTrue($finition->store(true));
        $first_id = $finition->id;

        $this->assertCount(1, $finition->getList());
        $listed_finition = $finition->getList()[0];
        $this->assertInstanceOf(\ArrayObject::class, $listed_finition);
        $this->assertGreaterThan(0, $listed_finition->id_finition);
        $this->assertSame('Feline', $listed_finition->finition);
        $this->assertSame('1 finition', $finition->displayCount());

        //add another one
        $finition = new \GaletteAuto\Finition($this->zdb);
        $finition->value = 'R';
        $this->assertTrue($finition->store(true));
        $id = $finition->id;

        $this->assertCount(2, $finition->getList());
        $this->assertSame('2 finitions', $finition->displayCount());

        $finition = new \GaletteAuto\Finition($this->zdb);
        $this->assertTrue($finition->load($id));
        $finition->value = 'RS';
        $this->assertTrue($finition->store());

        $this->assertCount(2, $finition->getList());
        $this->assertSame('2 finitions', $finition->displayCount());

        $finition = new \GaletteAuto\Finition($this->zdb);
        $this->assertTrue($finition->delete([$first_id]));
        $list = $finition->getList();
        $this->assertCount(1, $list);
        $last_finition = $list[0];
        $this->assertSame($id, (int)$last_finition->id_finition);
    }

    /**
     * Test load error
     *
     * @return void
     */
    public function testLoadError(): void
    {
        $finition = new \GaletteAuto\Finition($this->zdb);
        $this->assertFalse($finition->load(999));
    }

    /**
     * Test getClassName
     *
     * @return void
     */
    public function testGetClassName(): void
    {
        $this->assertSame('\\' . \GaletteAuto\Finition::class, \GaletteAuto\Finition::getClassForPropName('finition'));
    }
}
