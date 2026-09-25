<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\Controllers\tests\units;

use Galette\Tests\GaletteRoutingTestCase;
use GaletteAuto\Brand;
use GaletteAuto\Model;

/**
 * Models controller tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class ModelsController extends GaletteRoutingTestCase
{
    protected int $seed = 20260925111512;
    protected bool $load_plugins = true;

    private int $brand_id;

    /**
     * Set up tests
     */
    public function setUp(): void
    {
        parent::setUp();
        $brand = new Brand($this->zdb);
        $brand->value = 'Peugeot';
        $this->assertTrue($brand->store(true));
        $this->brand_id = $brand->id;
    }

    /**
     * Cleanup after each test method
     */
    public function tearDown(): void
    {
        $this->login->logout();
        parent::tearDown();
    }

    /**
     * Create a model, bypassing controller
     *
     * @param string $name Model name
     */
    private function createModel(string $name): int
    {
        $model = new Model($this->zdb);
        $this->assertTrue($model->check(['model' => $name, 'brand' => $this->brand_id]));
        $this->assertTrue($model->store(true));
        return $model->id;
    }

    /**
     * Stored model is the one from the route, not the posted one
     */
    public function testEditUsesRouteId(): void
    {
        $first = $this->createModel('307');
        $second = $this->createModel('308');

        $this->logSuperAdmin();
        $request = $this->createRequest('doModelEdit', ['id' => (string)$first], 'POST')
            ->withParsedBody([Model::PK => (string)$second, 'model' => '306', 'brand' => (string)$this->brand_id]);
        $test_response = $this->app->handle($request);
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('modelsList')]],
            $test_response->getHeaders()
        );
        $this->expectFlashData(['success_detected' => ['Model has been saved!']]);
        $this->assertSame('306', (new Model($this->zdb, $first))->model);
        $this->assertSame('308', (new Model($this->zdb, $second))->model);
    }

    /**
     * Posted values are shown back after a failed edition
     */
    public function testEditErrorKeepsPostedValues(): void
    {
        $id = $this->createModel('307');

        $this->logSuperAdmin();
        $request = $this->createRequest('doModelEdit', ['id' => (string)$id], 'POST')
            ->withParsedBody(['model' => 'Posted model', 'brand' => '-1']);
        $test_response = $this->app->handle($request);
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('modelEdit', ['id' => (string)$id])]],
            $test_response->getHeaders()
        );
        $this->expectFlashData(['error_detected' => ['- You must select a brand!']]);

        $request = $this->createRequest('modelEdit', ['id' => (string)$id]);
        $test_response = $this->app->handle($request);
        $this->assertSame(200, $test_response->getStatusCode());
        $this->assertStringContainsString('value="Posted model"', (string)$test_response->getBody());
        $this->assertSame('307', (new Model($this->zdb, $id))->model);
    }
}
