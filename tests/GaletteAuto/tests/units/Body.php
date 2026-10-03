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
 * Body tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Body extends GaletteTestCase
{
    protected int $seed = 20240130141727;

    /**
     * Test empty
     */
    public function testEmpty(): void
    {
        $body = new \GaletteAuto\Body($this->zdb);
        $bodies = new \GaletteAuto\Repository\Properties(
            $this->zdb,
            $this->preferences,
            $this->login,
            \GaletteAuto\Body::class
        );
        $this->assertSame('Body', $body->getFieldLabel());

        $this->assertCount(0, $bodies->getList());
        $this->assertSame('0 bodies', $body->getCountLabel($bodies->getCount()));
    }

    /**
     * Test add and update
     */
    public function testCrud(): void
    {
        $body = new \GaletteAuto\Body($this->zdb);
        $bodies = new \GaletteAuto\Repository\Properties(
            $this->zdb,
            $this->preferences,
            $this->login,
            \GaletteAuto\Body::class
        );
        //ensure the table is empty
        $this->assertCount(0, $bodies->getList());

        //Add new body
        $body->setValue('Coupe');
        $body->store(true);
        $first_id = $body->getId();

        $this->assertCount(1, $bodies->getList());
        $listed_body = $bodies->getList()[0];
        $this->assertInstanceOf(\GaletteAuto\Body::class, $listed_body);
        $this->assertGreaterThan(0, $listed_body->getId());
        $this->assertSame('Coupe', $listed_body->getValue());
        $this->assertSame('1 body', $body->getCountLabel($bodies->getCount()));

        //add another one
        $body = new \GaletteAuto\Body($this->zdb);
        $body->setValue('Brea');
        $body->store(true);
        $id = $body->getId();

        $this->assertCount(2, $bodies->getList());
        $this->assertSame('2 bodies', $body->getCountLabel($bodies->getCount()));

        $body = new \GaletteAuto\Body($this->zdb);
        $this->assertTrue($body->load($id));
        $body->setValue('Break');
        $body->store();

        $this->assertCount(2, $bodies->getList());
        $this->assertSame('2 bodies', $body->getCountLabel($bodies->getCount()));

        $bodies->remove([$first_id]);
        $list = $bodies->getList();
        $this->assertCount(1, $list);
        $last_body = $list[0];
        $this->assertSame($id, $last_body->getId());
    }

    /**
     * Test load error
     */
    public function testLoadError(): void
    {
        $body = new \GaletteAuto\Body($this->zdb);
        $this->expectNoLogEntry();
        $this->assertFalse($body->load(999));
        $this->expectLogEntry(
            \Analog\Analog::ERROR,
            '[GaletteAuto\Body] Cannot load body #999 | Record not found',
        );
    }

    /**
     * Test getClassName
     */
    public function testGetClassName(): void
    {
        $this->assertSame(\GaletteAuto\Body::class, \GaletteAuto\AbstractObject::getClassForPropName('body'));
    }
}
