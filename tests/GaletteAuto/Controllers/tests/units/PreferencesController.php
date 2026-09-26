<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\Controllers\tests\units;

use Galette\Tests\GaletteRoutingTestCase;
use GaletteAuto\AutoPreferences;

/**
 * Preferences controller tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class PreferencesController extends GaletteRoutingTestCase
{
    protected int $seed = 20260926191422;
    protected bool $load_plugins = true;

    /**
     * Restore default preferences
     */
    public function tearDown(): void
    {
        foreach (AutoPreferences::getSchema() as $name => $entry) {
            $this->assertTrue($this->preferences->setValue($name, (int)$entry['default'], $this->login));
        }
        $this->login->logout();
        parent::tearDown();
    }

    /**
     * Preferences page shows every toggle, checked or not
     */
    public function testPage(): void
    {
        $this->logSuperAdmin();
        $test_response = $this->app->handle($this->createRequest('autoPreferences'));
        $this->expectOK($test_response);
        $body = (string)$test_response->getBody();

        foreach (AutoPreferences::OPTIONAL_FIELDS as $field => $default) {
            $this->assertMatchesRegularExpression(
                '/name="pref_auto_required_' . $field . '"[^>]*value="1"\s*' . ($default ? 'checked' : '\/>') . '/',
                $body,
                $field
            );
        }
        $this->assertStringContainsString('<label for="chassis_number">Chassis number</label>', $body);
    }

    /**
     * Storing sets off the unchecked boxes
     */
    public function testStore(): void
    {
        $this->logSuperAdmin();
        $request = $this->createRequest('storeAutoPreferences', [], 'POST')
            ->withParsedBody([
                'pref_auto_required_name' => '1',
                'pref_auto_required_mileage' => '1',
                //not declared: ignored
                'pref_auto_required_model' => '0',
            ]);
        $test_response = $this->app->handle($request);
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('autoPreferences')]],
            $test_response->getHeaders()
        );
        $this->expectFlashData(['success_detected' => ['Preferences have been successfully stored!']]);

        $this->preferences->load();
        $this->assertSame(
            [
                'name' => true,
                'registration' => false,
                'fuel' => false,
                'mileage' => true,
                'seats' => false,
                'horsepower' => false,
                'engine_size' => false,
                'chassis_number' => false,
                'comment' => false,
            ],
            (new AutoPreferences($this->preferences))->toArray()
        );
    }

    /**
     * Preferences are for administrators only
     */
    public function testMemberAccess(): void
    {
        $this->getMemberOne();
        $mdata = $this->dataAdherentOne();
        $this->assertTrue($this->login->login($mdata['login_adh'], $mdata['mdp_adh']));
        $this->expectAuthMiddlewareRefused($this->app->handle($this->createRequest('autoPreferences')));
        $this->expectAuthMiddlewareRefused(
            $this->app->handle(
                $this->createRequest('storeAutoPreferences', [], 'POST')
                    ->withParsedBody(['pref_auto_required_name' => '1'])
            )
        );
        $this->assertTrue((new AutoPreferences($this->preferences))->isRequired('fuel'));
    }
}
