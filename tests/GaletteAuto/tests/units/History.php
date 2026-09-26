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
 * History tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class History extends GaletteTestCase
{
    protected int $seed = 20240130141727;

    /**
     * Test fields list; CRUD is tested along with Auto
     */
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
