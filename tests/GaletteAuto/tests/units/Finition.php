<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

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
        $this->assertSame('Finition', $finition->getFieldLabel());

        $this->assertCount(0, $finition->getList());
        $this->assertSame('0 finitions', $finition->displayCount());
    }

    /**
     * Test add and update
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
     */
    public function testLoadError(): void
    {
        $finition = new \GaletteAuto\Finition($this->zdb);
        $this->expectNoLogEntry();
        $this->assertFalse($finition->load(999));
        $this->expectLogEntry(
            \Analog::ERROR,
            '[GaletteAuto\Finition] Cannot load finitions from id `999`'
        );
    }

    /**
     * Test getClassName
     */
    public function testGetClassName(): void
    {
        $this->assertSame('\\' . \GaletteAuto\Finition::class, \GaletteAuto\Finition::getClassForPropName('finition'));
    }
}
