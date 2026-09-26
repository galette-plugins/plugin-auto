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
 * Color tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Color extends GaletteTestCase
{
    protected int $seed = 20240130141727;

    /**
     * Test empty
     */
    public function testEmpty(): void
    {
        $color = new \GaletteAuto\Color($this->zdb);
        $colors = new \GaletteAuto\Repository\Properties(
            $this->zdb,
            $this->preferences,
            $this->login,
            \GaletteAuto\Color::class
        );
        $this->assertSame('Color', $color->getFieldLabel());

        $this->assertCount(0, $colors->getList());
        $this->assertSame('0 colors', $color->getCountLabel($colors->getCount()));
    }

    /**
     * Test add and update
     */
    public function testCrud(): void
    {
        $color = new \GaletteAuto\Color($this->zdb);
        $colors = new \GaletteAuto\Repository\Properties(
            $this->zdb,
            $this->preferences,
            $this->login,
            \GaletteAuto\Color::class
        );
        //ensure the table is empty
        $this->assertCount(0, $colors->getList());

        //Add new color
        $color->setValue('Red');
        $this->assertTrue($color->store(true));
        $first_id = $color->getId();

        $this->assertCount(1, $colors->getList());
        $listed_color = $colors->getList()[0];
        $this->assertInstanceOf(\GaletteAuto\Color::class, $listed_color);
        $this->assertGreaterThan(0, $listed_color->getId());
        $this->assertSame('Red', $listed_color->getValue());
        $this->assertSame('1 color', $color->getCountLabel($colors->getCount()));

        //add another one
        $color = new \GaletteAuto\Color($this->zdb);
        $color->setValue('Blu');
        $this->assertTrue($color->store(true));
        $id = $color->getId();

        $this->assertCount(2, $colors->getList());
        $this->assertSame('2 colors', $color->getCountLabel($colors->getCount()));
        //sorted by value
        $this->assertSame(['Blu', 'Red'], array_map(fn($row) => $row->getValue(), $colors->getList()));

        $color = new \GaletteAuto\Color($this->zdb);
        $this->assertTrue($color->load($id));
        $color->setValue('Blue');
        $this->assertTrue($color->store());

        $this->assertCount(2, $colors->getList());
        $this->assertSame('2 colors', $color->getCountLabel($colors->getCount()));

        $colors->remove([$first_id]);
        $list = $colors->getList();
        $this->assertCount(1, $list);
        $last_color = $list[0];
        $this->assertSame($id, $last_color->getId());
    }

    /**
     * Test load error
     */
    public function testLoadError(): void
    {
        $color = new \GaletteAuto\Color($this->zdb);
        $this->expectNoLogEntry();
        $this->assertFalse($color->load(999));
        $this->expectLogEntry(
            \Analog\Analog::ERROR,
            '[GaletteAuto\Color] Cannot load colors from id `999`'
        );
    }

    /**
     * Test getClassName
     */
    public function testGetClassName(): void
    {
        $this->assertSame(\GaletteAuto\Color::class, \GaletteAuto\AbstractObject::getClassForPropName('color'));
    }
}
