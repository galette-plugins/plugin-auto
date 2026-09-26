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
 * Transmission tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Transmission extends GaletteTestCase
{
    protected int $seed = 20240130141727;

    /**
     * Test empty
     */
    public function testEmpty(): void
    {
        $transmission = new \GaletteAuto\Transmission($this->zdb);
        $transmissions = new \GaletteAuto\Repository\Properties(
            $this->zdb,
            $this->preferences,
            $this->login,
            \GaletteAuto\Transmission::class
        );
        $this->assertSame('Transmission', $transmission->getFieldLabel());

        $this->assertCount(0, $transmissions->getList());
        $this->assertSame('0 transmissions', $transmission->getCountLabel($transmissions->getCount()));
    }

    /**
     * Test add and update
     */
    public function testCrud(): void
    {
        $transmission = new \GaletteAuto\Transmission($this->zdb);
        $transmissions = new \GaletteAuto\Repository\Properties(
            $this->zdb,
            $this->preferences,
            $this->login,
            \GaletteAuto\Transmission::class
        );
        //ensure the table is empty
        $this->assertCount(0, $transmissions->getList());

        //Add new transmission
        $transmission->setValue('Manual');
        $transmission->store(true);
        $first_id = $transmission->getId();

        $this->assertCount(1, $transmissions->getList());
        $listed_transmission = $transmissions->getList()[0];
        $this->assertInstanceOf(\GaletteAuto\Transmission::class, $listed_transmission);
        $this->assertGreaterThan(0, $listed_transmission->getId());
        $this->assertSame('Manual', $listed_transmission->getValue());
        $this->assertSame('1 transmission', $transmission->getCountLabel($transmissions->getCount()));

        //add another one
        $transmission = new \GaletteAuto\Transmission($this->zdb);
        $transmission->setValue('Auto');
        $transmission->store(true);
        $id = $transmission->getId();

        $this->assertCount(2, $transmissions->getList());
        $this->assertSame('2 transmissions', $transmission->getCountLabel($transmissions->getCount()));

        $transmission = new \GaletteAuto\Transmission($this->zdb);
        $this->assertTrue($transmission->load($id));
        $transmission->setValue('Automatic');
        $transmission->store();

        $this->assertCount(2, $transmissions->getList());
        $this->assertSame('2 transmissions', $transmission->getCountLabel($transmissions->getCount()));

        $transmissions->remove([$first_id]);
        $list = $transmissions->getList();
        $this->assertCount(1, $list);
        $last_transmission = $list[0];
        $this->assertSame($id, $last_transmission->getId());
    }

    /**
     * Test load error
     */
    public function testLoadError(): void
    {
        $transmission = new \GaletteAuto\Transmission($this->zdb);
        $this->expectNoLogEntry();
        $this->assertFalse($transmission->load(999));
        $this->expectLogEntry(
            \Analog\Analog::ERROR,
            '[GaletteAuto\Transmission] Cannot load transmission #999 | Record not found'
        );
    }

    /**
     * Test getClassName
     */
    public function testGetClassName(): void
    {
        $this->assertSame(\GaletteAuto\Transmission::class, \GaletteAuto\AbstractObject::getClassForPropName('transmission'));
    }
}
