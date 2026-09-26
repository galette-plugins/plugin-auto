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
 * State tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class State extends GaletteTestCase
{
    protected int $seed = 20240130141727;

    /**
     * Test empty
     */
    public function testEmpty(): void
    {
        $state = new \GaletteAuto\State($this->zdb);
        $states = new \GaletteAuto\Repository\Properties(
            $this->zdb,
            $this->preferences,
            $this->login,
            \GaletteAuto\State::class
        );
        $this->assertSame('State', $state->getFieldLabel());

        $this->assertCount(0, $states->getList());
        $this->assertSame('0 states', $state->getCountLabel($states->getCount()));
    }

    /**
     * Test add and update
     */
    public function testCrud(): void
    {
        $state = new \GaletteAuto\State($this->zdb);
        $states = new \GaletteAuto\Repository\Properties(
            $this->zdb,
            $this->preferences,
            $this->login,
            \GaletteAuto\State::class
        );
        //ensure the table is empty
        $this->assertCount(0, $states->getList());

        //Add new state
        $state->setValue('Good');
        $this->assertTrue($state->store(true));
        $first_id = $state->getId();

        $this->assertCount(1, $states->getList());
        $listed_state = $states->getList()[0];
        $this->assertInstanceOf(\GaletteAuto\State::class, $listed_state);
        $this->assertGreaterThan(0, $listed_state->getId());
        $this->assertSame('Good', $listed_state->getValue());
        $this->assertSame('1 state', $state->getCountLabel($states->getCount()));

        //add another one
        $state = new \GaletteAuto\State($this->zdb);
        $state->setValue('Wrec');
        $this->assertTrue($state->store(true));
        $id = $state->getId();

        $this->assertCount(2, $states->getList());
        $this->assertSame('2 states', $state->getCountLabel($states->getCount()));

        $state = new \GaletteAuto\State($this->zdb);
        $this->assertTrue($state->load($id));
        $state->setValue('Wreck');
        $this->assertTrue($state->store());

        $this->assertCount(2, $states->getList());
        $this->assertSame('2 states', $state->getCountLabel($states->getCount()));

        $states->remove([$first_id]);
        $list = $states->getList();
        $this->assertCount(1, $list);
        $last_state = $list[0];
        $this->assertSame($id, $last_state->getId());
    }

    /**
     * Test load error
     */
    public function testLoadError(): void
    {
        $state = new \GaletteAuto\State($this->zdb);
        $this->expectNoLogEntry();
        $this->assertFalse($state->load(999));
        $this->expectLogEntry(
            \Analog\Analog::ERROR,
            '[GaletteAuto\State] Cannot load states from id `999`'
        );
    }

    /**
     * Test getClassName
     */
    public function testGetClassName(): void
    {
        $this->assertSame(\GaletteAuto\State::class, \GaletteAuto\AbstractObject::getClassForPropName('state'));
    }
}
