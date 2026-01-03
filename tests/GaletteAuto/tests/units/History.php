<?php

/**
 * Copyright © 2003-2025 The Galette Team
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
 * History tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class History extends GaletteTestCase
{
    protected int $seed = 20240130141727;

    //no crud tests here; they're part of Auto tests

    public function testGetFields(): void
    {
        $history = new \GaletteAuto\History($this->zdb);
        $this->assertCount(6, $history->fields);
    }

    /**
     * Test load error
     */
    public function testLoadError(): void
    {
        $history = new \GaletteAuto\History($this->zdb);
        $this->assertTrue($history->load(999));
        $this->assertCount(0, $history->getEntries());
    }
}
