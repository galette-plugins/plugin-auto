<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto;

use Galette\Core\Db;
use Galette\Core\Login;
use Galette\Core\Preferences;
use Galette\Entity\Adherent;

/**
 * Vehicles access rules
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class VehicleAccess
{
    /**
     * Constructor
     *
     * @param Db          $zdb         Database instance
     * @param Login       $login       Login instance
     * @param Preferences $preferences Preferences instance
     */
    public function __construct(
        private readonly Db $zdb,
        private readonly Login $login,
        private readonly Preferences $preferences
    ) {
    }

    /**
     * Get current user member ID
     */
    public function getMemberId(): int
    {
        return (int)$this->login->id;
    }

    /**
     * Can current user manage vehicles of other members?
     */
    public function isManager(): bool
    {
        return $this->login->isAdmin()
            || $this->login->isStaff()
            || $this->login->isGroupManager();
    }

    /**
     * Can current user manage vehicles of a member?
     *
     * Members manage their own vehicles; admins, staff members and managers
     * of one of the member's groups manage all their vehicles.
     *
     * @param int $id_adh Member ID
     */
    public function canManageMember(int $id_adh): bool
    {
        if ($this->login->isAdmin() || $this->login->isStaff()) {
            return true;
        }

        if (!$this->login->isLogged() || $id_adh <= 0) {
            return false;
        }

        if ($this->login->id == $id_adh) {
            return true;
        }

        $member = new Adherent($this->zdb);
        $member->disableAllDeps()->enableDep('groups')->enableDep('parent');
        if (!$member->load($id_adh)) {
            return false;
        }
        return $member->canShow($this->login);
    }

    /**
     * Can current user see the vehicles of every member?
     *
     * That is what the public vehicles page shows, photos included: whoever
     * its visibility lets in sees them all.
     */
    public function canViewVehicles(): bool
    {
        return $this->preferences->showPluginPublicPage($this->login, 'publicVehiclesList');
    }

    /**
     * Can the owner of a vehicle be named on the public vehicles page?
     *
     * Only members appearing in the members list are; the others' vehicles are
     * listed without them.
     *
     * @param Adherent $owner Vehicle owner
     */
    public function isOwnerPublic(Adherent $owner): bool
    {
        return $owner->appearsInMembersList();
    }
}
