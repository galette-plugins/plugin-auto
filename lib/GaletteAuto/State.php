<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto;

/**
 * Automobile States class for galette Auto plugin
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class State extends AbstractObject
{
    public const string TABLE = 'states';
    public const string PK = 'id_state';
    public const string FIELD = 'state';
    public const string LIST_ROUTE = 'statesList';

    /**
     * Get field label
     */
    public function getFieldLabel(): string
    {
        return _T('State', 'auto');
    }

    /**
     * Get list page title
     */
    public function getListTitle(): string
    {
        return _T("States list", "auto");
    }

    /**
     * Get add button text
     */
    public function getAddText(): string
    {
        return _T("Add new state", "auto");
    }

    /**
     * Get localized count
     *
     * @param int $count Count
     */
    public function getCountLabel(int $count): string
    {
        return str_replace(
            '%count',
            (string)$count,
            _Tn('%count state', '%count states', $count, 'auto')
        );
    }

    /**
     * Get removal success message
     *
     * @param int $count Removed records count
     */
    public function getRemovedMessage(int $count): string
    {
        return sprintf(
            _Tn('%1$s state has been successfully deleted.', '%1$s states have been successfully deleted.', $count, 'auto'),
            $count
        );
    }

    /**
     * Get message when removal is refused because the record is in use
     */
    public function getInUseMessage(): string
    {
        return _T('This state is used by one or more vehicles, it cannot be deleted.', 'auto');
    }

    /**
     * Get removal error message
     */
    public function getRemoveErrorMessage(): string
    {
        return _T('An error occurred trying to remove state :/', 'auto');
    }
}
