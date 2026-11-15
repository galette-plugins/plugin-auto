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
 * Plugin class tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class PluginGaletteAuto extends GaletteTestCase
{
    protected int $seed = 20260925112204;

    /**
     * Cleanup after each test method
     */
    public function tearDown(): void
    {
        $this->login->logout();
        parent::tearDown();
    }

    /**
     * Get menu items routes
     *
     * @return array<string>
     */
    private function getMenuRoutes(): array
    {
        $plugin = $this->container->get(\GaletteAuto\PluginGaletteAuto::class);
        $menus = $plugin->getMenus();
        return array_map(
            fn($item) => $item['route']['name'],
            $menus['plugin_auto']['items'] ?? []
        );
    }

    /**
     * Test menus by profile
     */
    public function testGetMenus(): void
    {
        $this->logSuperAdmin();
        $this->assertSame(
            [
                'typesList',
                'colorsList',
                'statesList',
                'finitionsList',
                'bodiesList',
                'transmissionsList',
                'brandsList',
                'modelsList',
                'vehiclesList',
            ],
            $this->getMenuRoutes()
        );
        $this->login->logout();

        $member_one = $this->getMemberOne();
        $member_two = $this->getMemberTwo();
        $group = new \Galette\Entity\Group();
        $group->setName('Auto group');
        $this->assertTrue($group->store());
        $this->assertTrue($group->setManagers([$member_two]));
        $this->assertTrue($group->setMembers([$member_one]));

        $mdata = $this->dataAdherentTwo();
        $this->assertTrue($this->login->login($mdata['login_adh'], $mdata['mdp_adh']));
        $this->assertSame(['modelsList', 'vehiclesList'], $this->getMenuRoutes());
        $this->login->logout();

        $mdata = $this->dataAdherentOne();
        $this->assertTrue($this->login->login($mdata['login_adh'], $mdata['mdp_adh']));
        $this->assertSame([], $this->getMenuRoutes());
    }

    /**
     * The public vehicles page is declared to the core, with a visibility of its own
     */
    public function testPublicPage(): void
    {
        $name = 'pref_auto_publicpages_visibility_vehicles';
        $plugin = $this->container->get(\GaletteAuto\PluginGaletteAuto::class);

        $this->assertSame(['vehicles' => ['routes' => ['publicVehiclesList']]], $plugin->getPublicPages());
        $this->assertSame('Vehicles', $plugin->getPublicPageLabel('vehicles'));
        $this->assertTrue(\Galette\Core\PreferencesSchema::isPublicPage($name));
        $this->assertSame($name, \Galette\Core\PreferencesSchema::getPublicPageRight('publicVehiclesList'));
        $this->assertSame(
            \Galette\Enums\PublicPageVisibility::Inherit->value,
            \Galette\Core\PreferencesSchema::get($name)['default']
        );

        //the public menu entry follows it, not the default visibility
        $this->setRawPreference('pref_bool_publicpages', value: true);
        $this->setRawPreference(
            'pref_publicpages_visibility_generic',
            \Galette\Enums\PublicPageVisibility::Hidden->value
        );
        $this->setRawPreference($name, \Galette\Enums\PublicPageVisibility::Everyone->value);
        $this->assertSame(['publicVehiclesList'], array_map(
            fn($item) => $item['route']['name'],
            $plugin->getPublicMenuItems()
        ));

        $this->setRawPreference($name, \Galette\Enums\PublicPageVisibility::Inherit->value);
        $this->assertSame([], $plugin->getPublicMenuItems());
    }

    /**
     * Tables where vehicles outlive their owner come from before 1.1; the
     * CI upgrade job covers them, schema changes cannot be rolled back on MySQL
     */
    public function testLegacyDbVersion(): void
    {
        $plugin = $this->container->get(\GaletteAuto\PluginGaletteAuto::class);
        $this->assertNull($plugin->getLegacyDbVersion());
    }

    /**
     * Default vehicle types are created on install
     */
    public function testInstallInit(): void
    {
        $plugin = $this->container->get(\GaletteAuto\PluginGaletteAuto::class);
        $plugin->installInit($this->zdb);

        $types = [];
        foreach ($this->zdb->execute($this->zdb->select(AUTO_PREFIX . \GaletteAuto\VehicleType::TABLE)) as $row) {
            $types[$row->type] = new \GaletteAuto\VehicleType($this->zdb, (int)$row->id_type);
        }
        $this->assertSame(['Car', 'Motorbike'], array_keys($types));

        $this->assertTrue($types['Car']->isRequired('registration'));
        $this->assertFalse($types['Car']->isHidden('body'));
        $this->assertTrue($types['Motorbike']->isRequired('registration'));
        $this->assertTrue($types['Motorbike']->isHidden('body'));
        $this->assertFalse($types['Motorbike']->isHidden('transmission'));
    }
}
