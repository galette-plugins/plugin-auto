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
     * Set up tests
     */
    public function setUp(): void
    {
        parent::setUp();
        //start without the vehicle types the plugin creates on install or upgrade;
        //removed in the test transaction, they are back once it is rolled back
        $this->zdb->execute($this->zdb->delete(AUTO_PREFIX . \GaletteAuto\VehicleType::FIELDS_TABLE));
        $this->zdb->execute($this->zdb->delete(AUTO_PREFIX . \GaletteAuto\VehicleType::TABLE));
    }

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
     * Test fields modes
     */
    public function testFields(): void
    {
        $type = new \GaletteAuto\VehicleType($this->zdb);
        //a new type uses every field, none required
        $this->assertSame(
            array_fill_keys(\GaletteAuto\VehicleType::FIELDS, \GaletteAuto\VehicleType::OPTIONAL),
            $type->getFields()
        );
        $this->assertSame(['type' => true, 'model' => true], $type->getRequired());

        $type->setValue('Moped');
        $type->setFields([
            'body' => \GaletteAuto\VehicleType::HIDDEN,
            'transmission' => (string)\GaletteAuto\VehicleType::HIDDEN,
            'name' => \GaletteAuto\VehicleType::REQUIRED,
            //ignored
            'model' => \GaletteAuto\VehicleType::HIDDEN,
            'unknown' => \GaletteAuto\VehicleType::REQUIRED,
            'color' => 42,
            'state' => 'hidden',
        ]);
        $type->store(new: true);

        $loaded = new \GaletteAuto\VehicleType($this->zdb);
        $this->assertTrue($loaded->load((int)$type->getId()));
        $this->assertTrue($loaded->isHidden('body'));
        $this->assertTrue($loaded->isHidden('transmission'));
        $this->assertFalse($loaded->isHidden('color'));
        $this->assertFalse($loaded->isRequired('color'));
        $this->assertFalse($loaded->isHidden('model'));
        $this->assertTrue($loaded->isRequired('model'));
        $this->assertTrue($loaded->isRequired('type'));
        $this->assertSame(\GaletteAuto\VehicleType::OPTIONAL, $loaded->getFieldMode('state'));
        $this->assertSame(\GaletteAuto\VehicleType::OPTIONAL, $loaded->getFieldMode('unknown'));
        $this->assertSame(['type' => true, 'model' => true, 'name' => true], $loaded->getRequired());
        $this->assertCount(count(\GaletteAuto\VehicleType::FIELDS), $loaded->getFields());

        //update replaces modes
        $loaded->setFields(['body' => \GaletteAuto\VehicleType::OPTIONAL]);
        $loaded->store();
        $this->assertTrue($type->load((int)$type->getId()));
        $this->assertFalse($type->isHidden('body'));
        $this->assertTrue($type->isHidden('transmission'));

        //a value update keeps modes
        $type->setValue('Mobylette');
        $type->store();
        $this->assertTrue($loaded->load((int)$type->getId()));
        $this->assertSame('Mobylette', $loaded->getValue());
        $this->assertTrue($loaded->isHidden('transmission'));

        //modes go along with their type
        $types = new \GaletteAuto\Repository\Properties(
            zdb: $this->zdb,
            preferences: $this->preferences,
            login: $this->login,
            class: \GaletteAuto\VehicleType::class
        );
        $types->remove([(int)$type->getId()]);
        $select = $this->zdb->select(AUTO_PREFIX . \GaletteAuto\VehicleType::FIELDS_TABLE);
        $select->where([\GaletteAuto\VehicleType::PK => $type->getId()]);
        $this->assertSame(0, $this->zdb->execute($select)->count());
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
