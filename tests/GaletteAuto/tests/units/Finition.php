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
 * Finition tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Finition extends GaletteTestCase
{
    protected int $seed = 20240130141727;

    /**
     * Test empty
     */
    public function testEmpty(): void
    {
        $finition = new \GaletteAuto\Finition($this->zdb);
        $finitions = new \GaletteAuto\Repository\Properties(
            $this->zdb,
            $this->preferences,
            $this->login,
            \GaletteAuto\Finition::class
        );
        $this->assertSame('Finition', $finition->getFieldLabel());

        $this->assertCount(0, $finitions->getList());
        $this->assertSame('0 finitions', $finition->getCountLabel($finitions->getCount()));
    }

    /**
     * Test add and update
     */
    public function testCrud(): void
    {
        $finition = new \GaletteAuto\Finition($this->zdb);
        $finitions = new \GaletteAuto\Repository\Properties(
            $this->zdb,
            $this->preferences,
            $this->login,
            \GaletteAuto\Finition::class
        );
        //ensure the table is empty
        $this->assertCount(0, $finitions->getList());

        //Add new finition
        $finition->setValue('Feline');
        $finition->store(true);
        $first_id = $finition->getId();

        $this->assertCount(1, $finitions->getList());
        $listed_finition = $finitions->getList()[0];
        $this->assertInstanceOf(\GaletteAuto\Finition::class, $listed_finition);
        $this->assertGreaterThan(0, $listed_finition->getId());
        $this->assertSame('Feline', $listed_finition->getValue());
        $this->assertSame('1 finition', $finition->getCountLabel($finitions->getCount()));

        //add another one
        $finition = new \GaletteAuto\Finition($this->zdb);
        $finition->setValue('R');
        $finition->store(true);
        $id = $finition->getId();

        $this->assertCount(2, $finitions->getList());
        $this->assertSame('2 finitions', $finition->getCountLabel($finitions->getCount()));

        $finition = new \GaletteAuto\Finition($this->zdb);
        $this->assertTrue($finition->load($id));
        $finition->setValue('RS');
        $finition->store();

        $this->assertCount(2, $finitions->getList());
        $this->assertSame('2 finitions', $finition->getCountLabel($finitions->getCount()));

        $finitions->remove([$first_id]);
        $list = $finitions->getList();
        $this->assertCount(1, $list);
        $last_finition = $list[0];
        $this->assertSame($id, $last_finition->getId());
    }

    /**
     * Test load error
     */
    public function testLoadError(): void
    {
        $finition = new \GaletteAuto\Finition($this->zdb);
        $this->expectNoLogEntry();
        $this->assertFalse($finition->load(999));
        $this->expectLogEntry(
            \Analog\Analog::ERROR,
            '[GaletteAuto\Finition] Cannot load finition #999 | Record not found'
        );
    }

    /**
     * Test getClassName
     */
    public function testGetClassName(): void
    {
        $this->assertSame(\GaletteAuto\Finition::class, \GaletteAuto\AbstractObject::getClassForPropName('finition'));
    }
}
