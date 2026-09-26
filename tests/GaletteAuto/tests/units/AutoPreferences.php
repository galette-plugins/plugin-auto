<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\tests\units;

use Galette\Core\PreferencesSchema;
use Galette\Tests\GaletteTestCase;

/**
 * Plugin preferences tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class AutoPreferences extends GaletteTestCase
{
    protected int $seed = 20260926190512;
    protected bool $load_plugins = true;

    /**
     * Restore default preferences
     */
    public function tearDown(): void
    {
        foreach (\GaletteAuto\AutoPreferences::getSchema() as $name => $entry) {
            $this->assertTrue($this->preferences->setValue($name, (int)$entry['default'], $this->login));
        }
        $this->login->logout();
        parent::tearDown();
    }

    /**
     * Preferences are declared to core, with former required fields as defaults
     */
    public function testDefaults(): void
    {
        $this->assertSame(
            [
                'name' => true,
                'registration' => true,
                'fuel' => true,
                'mileage' => false,
                'seats' => false,
                'horsepower' => false,
                'engine_size' => false,
                'chassis_number' => false,
                'comment' => false,
            ],
            (new \GaletteAuto\AutoPreferences($this->preferences))->toArray()
        );
        $this->assertSame(
            ['type' => PreferencesSchema::TYPE_BOOL, 'default' => true, 'plugin' => 'auto'],
            PreferencesSchema::get('pref_auto_required_fuel')
        );

        $required = (new \GaletteAuto\AutoPreferences($this->preferences))->getRequired();
        $this->assertSame(
            [
                'model',
                'first_registration_date',
                'first_circulation_date',
                'color',
                'state',
                'body',
                'transmission',
                'finition',
                'name',
                'registration',
                'fuel',
            ],
            array_keys($required)
        );
    }

    /**
     * Former local configuration file gives the defaults
     */
    public function testLegacyFile(): void
    {
        $path = sys_get_temp_dir() . '/galette-auto-' . uniqid() . '/';
        mkdir($path);
        $file = $path . \GaletteAuto\AutoPreferences::LEGACY_FILE;
        file_put_contents(
            $file,
            "<?php\nreturn ['name' => 1, 'model' => 1, 'mileage' => 1, 'comment' => 1];\n"
        );

        try {
            $defaults = array_map(
                fn(array $entry) => $entry['default'],
                \GaletteAuto\AutoPreferences::getSchema($path)
            );
        } finally {
            unlink($file);
            rmdir($path);
        }

        $this->assertSame(
            [
                'pref_auto_required_name' => true,
                'pref_auto_required_registration' => false,
                'pref_auto_required_fuel' => false,
                'pref_auto_required_mileage' => true,
                'pref_auto_required_seats' => false,
                'pref_auto_required_horsepower' => false,
                'pref_auto_required_engine_size' => false,
                'pref_auto_required_chassis_number' => false,
                'pref_auto_required_comment' => true,
            ],
            $defaults
        );
    }

    /**
     * Stored preferences decide which fields are required
     */
    public function testRequired(): void
    {
        $this->assertTrue($this->preferences->setValue('pref_auto_required_fuel', 0, $this->login));
        $this->assertTrue($this->preferences->setValue('pref_auto_required_comment', 1, $this->login));

        $prefs = new \GaletteAuto\AutoPreferences($this->preferences);
        $this->assertFalse($prefs->isRequired('fuel'));
        $this->assertTrue($prefs->isRequired('comment'));
        //always required, and not a field at all
        $this->assertTrue($prefs->isRequired('model'));
        $this->assertFalse($prefs->isRequired('owner_id'));

        //empty fields are checked accordingly
        $this->logSuperAdmin();
        $auto = new \GaletteAuto\Auto($this->container->get(\Galette\Core\Plugins::class), $this->zdb);
        $this->assertFalse(
            $auto->check([], new \GaletteAuto\VehicleAccess($this->zdb, $this->login, $this->preferences), $prefs)
        );
        $errors = $auto->getErrors();
        $this->assertContains('- Mandatory field <a href="#comment">comment</a> empty.', $errors);
        $this->assertNotContains('- Mandatory field <a href="#fuel">fuel</a> empty.', $errors);
        $this->assertNotContains('- You must choose a fuel in the list', $errors);
        $this->assertNull($auto->getFuel());
    }
}
