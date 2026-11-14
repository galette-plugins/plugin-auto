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
 * Vehicle types tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class VehicleType extends GaletteTestCase
{
    protected int $seed = 20240130141727;

    /**
     * Test empty
     */
    public function testEmpty(): void
    {
        $type = new \GaletteAuto\VehicleType($this->zdb);
        $types = new \GaletteAuto\Repository\Properties(
            zdb: $this->zdb,
            preferences: $this->preferences,
            login: $this->login,
            class: \GaletteAuto\VehicleType::class
        );
        $this->assertSame('Vehicle type', $type->getFieldLabel());

        $this->assertCount(0, $types->getList());
    }

    /**
     * Test add and update
     */
    public function testCrud(): void
    {
        $type = new \GaletteAuto\VehicleType($this->zdb);
        $types = new \GaletteAuto\Repository\Properties(
            zdb: $this->zdb,
            preferences: $this->preferences,
            login: $this->login,
            class: \GaletteAuto\VehicleType::class
        );
        //ensure the table is empty
        $this->assertCount(0, $types->getList());

        //Add new type
        $type->setValue('Moped');
        $type->store(new: true);
        $first_id = $type->getId();

        $this->assertCount(1, $types->getList());
        $listed_type = $types->getList()[0];
        $this->assertInstanceOf(\GaletteAuto\VehicleType::class, $listed_type);
        $this->assertGreaterThan(0, $listed_type->getId());
        $this->assertSame('Moped', $listed_type->getValue());
        $this->assertSame('1 vehicle type', $type->getCountLabel($types->getCount()));

        //add another one
        $type = new \GaletteAuto\VehicleType($this->zdb);
        $type->setValue('Car');
        $type->store(new: true);
        $id = $type->getId();

        $this->assertCount(2, $types->getList());
        $this->assertSame('2 vehicle types', $type->getCountLabel($types->getCount()));
        //sorted by value
        $this->assertSame(['Car', 'Moped'], array_map(fn($row) => $row->getValue(), $types->getList()));

        $type = new \GaletteAuto\VehicleType($this->zdb);
        $this->assertTrue($type->load($id));
        $type->setValue('Motorbike');
        $type->store();

        $this->assertCount(2, $types->getList());
        $this->assertSame('2 vehicle types', $type->getCountLabel($types->getCount()));

        $types->remove([$first_id]);
        $list = $types->getList();
        $this->assertCount(1, $list);
        $last_type = $list[0];
        $this->assertSame($id, $last_type->getId());
    }

    /**
     * Test load error
     */
    public function testLoadError(): void
    {
        $type = new \GaletteAuto\VehicleType($this->zdb);
        $this->expectNoLogEntry();
        $this->assertFalse($type->load(999));
        $this->expectLogEntry(
            \Analog\Analog::ERROR,
            '[GaletteAuto\VehicleType] Cannot load type #999 | Record not found'
        );
    }

    /**
     * Test getClassName
     */
    public function testGetClassName(): void
    {
        $this->assertSame(\GaletteAuto\VehicleType::class, \GaletteAuto\AbstractObject::getClassForPropName('type'));
    }
}
