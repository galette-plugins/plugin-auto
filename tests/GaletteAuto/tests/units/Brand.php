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
 * Brand tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Brand extends GaletteTestCase
{
    protected int $seed = 20240130141727;

    /**
     * Cleanup after each test method
     *
     * @return void
     */
    public function tearDown(): void
    {
        $delete = $this->zdb->delete(AUTO_PREFIX . \GaletteAuto\Brand::TABLE);
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
        $brand = new \GaletteAuto\Brand($this->zdb);
        $this->assertSame('Brand', $brand->getFieldLabel());

        $this->assertCount(0, $brand->getList());
        $this->assertSame('0 brand', $brand->displayCount());
    }

    /**
     * Test add and update
     *
     * @return void
     */
    public function testCrud(): void
    {
        $brand = new \GaletteAuto\Brand($this->zdb);
        //ensure the table is empty
        $this->assertCount(0, $brand->getList());

        //Add new brand
        $brand->value = 'Audi';
        $this->assertTrue($brand->store(true));
        $first_id = $brand->id;

        $this->assertCount(1, $brand->getList());
        $listed_brand = $brand->getList()[0];
        $this->assertInstanceOf(\ArrayObject::class, $listed_brand);
        $this->assertGreaterThan(0, $listed_brand->id_brand);
        $this->assertSame('Audi', $listed_brand->brand);
        $this->assertSame('1 brand', $brand->displayCount());

        //add another one
        $brand = new \GaletteAuto\Brand($this->zdb);
        $brand->value = 'Mercede';
        $this->assertTrue($brand->store(true));
        $id = $brand->id;

        $this->assertCount(2, $brand->getList());
        $this->assertSame('2 brands', $brand->displayCount());

        $brand = new \GaletteAuto\Brand($this->zdb);
        $this->assertTrue($brand->load($id));
        $brand->value = 'Mercedes';
        $this->assertTrue($brand->store());

        $this->assertCount(2, $brand->getList());
        $this->assertSame('2 brands', $brand->displayCount());

        $brand = new \GaletteAuto\Brand($this->zdb);
        $this->assertTrue($brand->delete([$first_id]));
        $list = $brand->getList();
        $this->assertCount(1, $list);
        $last_brand = $list[0];
        $this->assertSame($id, (int)$last_brand->id_brand);
    }

    /**
     * Test load error
     *
     * @return void
     */
    public function testLoadError(): void
    {
        $brand = new \GaletteAuto\Brand($this->zdb);
        $this->assertFalse($brand->load(999));
    }

    /**
     * Test getClassName
     *
     * @return void
     */
    public function testGetClassName(): void
    {
        $this->assertSame('\\' . \GaletteAuto\Brand::class, \GaletteAuto\Brand::getClassForPropName('brand'));
    }
}
