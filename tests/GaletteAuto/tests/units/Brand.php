<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace GaletteAuto\tests\units;

use Galette\Tests\GaletteTestCase;

/**
 * Brand tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Brand extends GaletteTestCase
{
    protected int $seed = 20240130141727;

    /**
     * Test empty
     */
    public function testEmpty(): void
    {
        $brand = new \GaletteAuto\Brand($this->zdb);
        $this->assertSame('Brand', $brand->getFieldLabel());

        $this->assertCount(0, $brand->getList());
        $this->assertSame('0 brands', $brand->displayCount());
    }

    /**
     * Test add and update
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
     */
    public function testLoadError(): void
    {
        $brand = new \GaletteAuto\Brand($this->zdb);
        $this->expectNoLogEntry();
        $this->assertFalse($brand->load(999));
        $this->expectLogEntry(
            \Analog::ERROR,
            '[GaletteAuto\Brand] Cannot load brands from id `999`'
        );
    }

    /**
     * Test getClassName
     */
    public function testGetClassName(): void
    {
        $this->assertSame('\\' . \GaletteAuto\Brand::class, \GaletteAuto\Brand::getClassForPropName('brand'));
    }
}
