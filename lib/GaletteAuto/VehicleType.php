<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto;

/**
 * Vehicle types (car, motorbike, moped, truck...) for galette Auto plugin
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class VehicleType extends AbstractObject
{
    public const string TABLE = 'types';
    public const string PK = 'id_type';
    public const string FIELD = 'type';
    public const string LIST_ROUTE = 'typesList';

    /**
     * Get sequence name on PostgreSQL: the table uses an identity column
     */
    protected function getSequenceName(): string
    {
        return PREFIX_DB . AUTO_PREFIX . static::TABLE . '_' . static::PK . '_seq';
    }

    /**
     * Get field label
     */
    public function getFieldLabel(): string
    {
        return _T('Vehicle type', 'auto');
    }

    /**
     * Get list page title
     */
    public function getListTitle(): string
    {
        return _T("Vehicle types list", "auto");
    }

    /**
     * Get add button text
     */
    public function getAddText(): string
    {
        return _T("Add new vehicle type", "auto");
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
            _Tn(singular: '%count vehicle type', plural: '%count vehicle types', count: $count, domain: 'auto')
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
            _Tn(
                singular: '%1$s vehicle type has been successfully deleted.',
                plural: '%1$s vehicle types have been successfully deleted.',
                count: $count,
                domain: 'auto'
            ),
            $count
        );
    }

    /**
     * Get message when removal is refused because the record is in use
     */
    public function getInUseMessage(): string
    {
        return _T('This vehicle type is used by one or more vehicles, it cannot be deleted.', 'auto');
    }

    /**
     * Get removal error message
     */
    public function getRemoveErrorMessage(): string
    {
        return _T('An error occurred trying to remove vehicle type :/', 'auto');
    }
}
