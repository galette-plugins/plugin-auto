<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\Controllers\tests\units;

use Galette\Tests\GaletteRoutingTestCase;
use GaletteAuto\Color;

/**
 * Properties controller tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class PropertiesController extends GaletteRoutingTestCase
{
    protected int $seed = 20260925111034;
    protected bool $load_plugins = true;

    /**
     * Cleanup after each test method
     */
    public function tearDown(): void
    {
        $this->login->logout();
        parent::tearDown();
    }

    /**
     * Create a color, bypassing controller
     *
     * @param string $value Color name
     */
    private function createColor(string $value): int
    {
        $color = new Color($this->zdb);
        $color->value = $value;
        $this->assertTrue($color->store(true));
        return $color->id;
    }

    /**
     * Get color name from database
     *
     * @param int $id Color ID
     */
    private function getColor(int $id): string
    {
        $color = new Color($this->zdb);
        $this->assertTrue($color->load($id));
        return $color->value;
    }

    /**
     * Stored property is the one from the route, not the posted one
     */
    public function testEditUsesRouteId(): void
    {
        $red = $this->createColor('Red');
        $blue = $this->createColor('Blue');

        $this->logSuperAdmin();
        $request = $this->createRequest(
            'doPropertyEdit',
            ['property' => 'color', 'id' => (string)$red],
            'POST'
        )->withParsedBody([Color::PK => (string)$blue, 'color' => 'Dark red']);
        $test_response = $this->app->handle($request);
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('colorsList')]],
            $test_response->getHeaders()
        );
        $this->expectFlashData(['success_detected' => ['Color has been saved!']]);
        $this->assertSame('Dark red', $this->getColor($red));
        $this->assertSame('Blue', $this->getColor($blue));
    }

    /**
     * Adding an empty property goes back to add form
     */
    public function testAddError(): void
    {
        $this->logSuperAdmin();
        $request = $this->createRequest('doPropertyAdd', ['property' => 'color'], 'POST')
            ->withParsedBody(['color' => '']);
        $test_response = $this->app->handle($request);
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('propertyAdd', ['property' => 'color'])]],
            $test_response->getHeaders()
        );
        $this->expectFlashData(['error_detected' => ['- You must provide a value!']]);
    }
}
