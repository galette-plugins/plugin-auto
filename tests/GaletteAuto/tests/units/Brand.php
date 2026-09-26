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
        $brands = new \GaletteAuto\Repository\Properties(
            $this->zdb,
            $this->preferences,
            $this->login,
            \GaletteAuto\Brand::class
        );
        $this->assertSame('Brand', $brand->getFieldLabel());

        $this->assertCount(0, $brands->getList());
        $this->assertSame('0 brands', $brand->getCountLabel($brands->getCount()));
    }

    /**
     * Test add and update
     */
    public function testCrud(): void
    {
        $brand = new \GaletteAuto\Brand($this->zdb);
        $brands = new \GaletteAuto\Repository\Properties(
            $this->zdb,
            $this->preferences,
            $this->login,
            \GaletteAuto\Brand::class
        );
        //ensure the table is empty
        $this->assertCount(0, $brands->getList());

        //Add new brand
        $brand->setValue('Audi');
        $this->assertTrue($brand->store(true));
        $first_id = $brand->getId();

        $this->assertCount(1, $brands->getList());
        $listed_brand = $brands->getList()[0];
        $this->assertInstanceOf(\GaletteAuto\Brand::class, $listed_brand);
        $this->assertGreaterThan(0, $listed_brand->getId());
        $this->assertSame('Audi', $listed_brand->getValue());
        $this->assertSame('1 brand', $brand->getCountLabel($brands->getCount()));

        //add another one
        $brand = new \GaletteAuto\Brand($this->zdb);
        $brand->setValue('Mercede');
        $this->assertTrue($brand->store(true));
        $id = $brand->getId();

        $this->assertCount(2, $brands->getList());
        $this->assertSame('2 brands', $brand->getCountLabel($brands->getCount()));

        $brand = new \GaletteAuto\Brand($this->zdb);
        $this->assertTrue($brand->load($id));
        $brand->setValue('Mercedes');
        $this->assertTrue($brand->store());

        $this->assertCount(2, $brands->getList());
        $this->assertSame('2 brands', $brand->getCountLabel($brands->getCount()));

        $brands->remove([$first_id]);
        $list = $brands->getList();
        $this->assertCount(1, $list);
        $last_brand = $list[0];
        $this->assertSame($id, $last_brand->getId());
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
            \Analog\Analog::ERROR,
            '[GaletteAuto\Brand] Cannot load brands from id `999`'
        );
    }

    /**
     * Test getClassName
     */
    public function testGetClassName(): void
    {
        $this->assertSame(\GaletteAuto\Brand::class, \GaletteAuto\AbstractObject::getClassForPropName('brand'));
    }
}
