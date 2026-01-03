<?php

/**
 * Copyright © 2003-2026 The Galette Team
 *
 * This file is part of Galette (https://galette.eu).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 */

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
     * Cleanup after each test method
     */
    public function tearDown(): void
    {
        $delete = $this->zdb->delete(AUTO_PREFIX . \GaletteAuto\Body::TABLE);
        $this->zdb->execute($delete);
        parent::tearDown();
    }

    /**
     * Test empty
     */
    public function testEmpty(): void
    {
        $body = new \GaletteAuto\Body($this->zdb);
        $this->assertSame('Body', $body->getFieldLabel());

        $this->assertCount(0, $body->getList());
        $this->assertSame('0 body', $body->displayCount());
    }

    /**
     * Test add and update
     */
    public function testCrud(): void
    {
        $body = new \GaletteAuto\Body($this->zdb);
        //ensure the table is empty
        $this->assertCount(0, $body->getList());

        //Add new body
        $body->value = 'Coupe';
        $this->assertTrue($body->store(true));
        $first_id = $body->id;

        $this->assertCount(1, $body->getList());
        $listed_body = $body->getList()[0];
        $this->assertInstanceOf(\ArrayObject::class, $listed_body);
        $this->assertGreaterThan(0, $listed_body->id_body);
        $this->assertSame('Coupe', $listed_body->body);
        $this->assertSame('1 body', $body->displayCount());

        //add another one
        $body = new \GaletteAuto\Body($this->zdb);
        $body->value = 'Brea';
        $this->assertTrue($body->store(true));
        $id = $body->id;

        $this->assertCount(2, $body->getList());
        $this->assertSame('2 bodies', $body->displayCount());

        $body = new \GaletteAuto\Body($this->zdb);
        $this->assertTrue($body->load($id));
        $body->value = 'Break';
        $this->assertTrue($body->store());

        $this->assertCount(2, $body->getList());
        $this->assertSame('2 bodies', $body->displayCount());

        $body = new \GaletteAuto\Body($this->zdb);
        $this->assertTrue($body->delete([$first_id]));
        $list = $body->getList();
        $this->assertCount(1, $list);
        $last_body = $list[0];
        $this->assertSame($id, (int)$last_body->id_body);
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
            \Analog::ERROR,
            '[GaletteAuto\Body] Cannot load bodies from id `999`',
        );
    }

    /**
     * Test getClassName
     */
    public function testGetClassName(): void
    {
        $this->assertSame('\\' . \GaletteAuto\Body::class, \GaletteAuto\Body::getClassForPropName('body'));
    }
}
