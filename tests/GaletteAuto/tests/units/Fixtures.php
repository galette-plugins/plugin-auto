<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\tests\units;

use Galette\Core\Plugins\FixturesContext;
use Galette\Tests\GaletteTestCase;

/**
 * Fixtures tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Fixtures extends GaletteTestCase
{
    protected int $seed = 20261004140000;
    protected bool $load_plugins = true;

    protected \Galette\Core\Plugins $plugins;

    /**
     * Set up tests
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->plugins = $this->container->get(\Galette\Core\Plugins::class);
    }

    /**
     * Count rows of a plugin table
     *
     * @param string $table Table name, without prefixes
     */
    private function countRows(string $table): int
    {
        return $this->zdb->execute($this->zdb->select(AUTO_PREFIX . $table))->count();
    }

    /**
     * Test seeding and cleaning fixtures
     */
    public function testFixtures(): void
    {
        $member_one = $this->getMemberOne();
        $member_two = $this->getMemberTwo();
        $this->logSuperAdmin();

        //a value fixtures also use, that must be kept
        $color = new \GaletteAuto\Color($this->zdb);
        $color->setValue('Rouge');
        $color->store(true);
        $colors = $this->countRows(\GaletteAuto\Color::TABLE);

        $context = new FixturesContext(
            zdb: $this->zdb,
            login: $this->login,
            preferences: $this->preferences,
            history: $this->history,
            plugins: $this->plugins,
            members: ['one' => $member_one->id, 'two' => $member_two->id]
        );

        $fixtures = new \GaletteAuto\Fixtures();
        $this->assertSame('Created 15 vehicles', $fixtures->seedFixtures($context));
        $this->assertSame(15, $this->countRows(\GaletteAuto\Auto::TABLE));
        //existing value is reused
        $select = $this->zdb->select(AUTO_PREFIX . \GaletteAuto\Color::TABLE)->where(['color' => 'Rouge']);
        $this->assertSame(1, $this->zdb->execute($select)->count());

        //vehicles are owned by context members, added before today
        $today = date('Y-m-d');
        foreach ($this->zdb->execute($this->zdb->select(AUTO_PREFIX . \GaletteAuto\Auto::TABLE)) as $row) {
            $this->assertContains((int)$row->id_adh, [$member_one->id, $member_two->id]);
            $this->assertLessThan($today, $row->car_creation_date);
        }

        //one history entry per vehicle, and a former owner for one of them
        $this->assertSame(16, $this->countRows(\GaletteAuto\History::TABLE));
        $select = $this->zdb->select(AUTO_PREFIX . \GaletteAuto\History::TABLE);
        $select->where->lessThan('history_date', $today);
        $this->assertSame(16, $this->zdb->execute($select)->count());

        $fixtures->cleanFixtures($context);
        $this->assertSame(0, $this->countRows(\GaletteAuto\Auto::TABLE));
        $this->assertSame(0, $this->countRows(\GaletteAuto\History::TABLE));
        $this->assertSame(0, $this->countRows(\GaletteAuto\Model::TABLE));
        $this->assertSame(0, $this->countRows(\GaletteAuto\Brand::TABLE));
        $this->assertSame(0, $this->countRows(\GaletteAuto\Body::TABLE));
        //color that existed before seeding is gone as well: nothing tells it from a fixture one
        $this->assertSame($colors - 1, $this->countRows(\GaletteAuto\Color::TABLE));
    }

    /**
     * Nothing is created without members
     */
    public function testNoMembers(): void
    {
        $this->logSuperAdmin();
        $context = new FixturesContext(
            zdb: $this->zdb,
            login: $this->login,
            preferences: $this->preferences,
            history: $this->history,
            plugins: $this->plugins
        );
        $this->assertSame('No member to give vehicles to', (new \GaletteAuto\Fixtures())->seedFixtures($context));
        $this->assertSame(0, $this->countRows(\GaletteAuto\Auto::TABLE));
    }
}
