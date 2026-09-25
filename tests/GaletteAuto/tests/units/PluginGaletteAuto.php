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
}
