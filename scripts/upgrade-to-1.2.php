<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\Updates;

use Galette\Updater\AbstractUpdater;
use GaletteAuto\VehicleType;

/**
 * Galette Auto 1.2 upgrade script: vehicle types
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class UpgradeTo12 extends AbstractUpdater
{
    protected ?string $db_version = '1.2';

    /**
     * Main constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setSqlScripts($this->db_version);
    }

    /**
     * Update instructions
     */
    protected function update(): bool
    {
        return true;
    }

    /**
     * Name the type existing vehicles were given, once SQL script has run
     */
    protected function postUpdate(): bool
    {
        $update = $this->zdb->update(AUTO_PREFIX . VehicleType::TABLE);
        $update->set([VehicleType::FIELD => _T('Car', 'auto')]);
        $update->where([VehicleType::FIELD => 'Car']);
        $this->zdb->execute($update);
        return true;
    }
}
