<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto;

use Galette\Core\Db;

/**
 * Automobile Transmissions class for galette Auto plugin
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Transmission extends AbstractObject
{
    public const string TABLE = 'transmissions';
    public const string PK = 'id_transmission';
    public const string FIELD = 'transmission';
    public const string NAME = 'transmissions';

    /**
     * Default constructor
     *
     * @param Db   $zdb Database instance
     * @param ?int $id  transmission's id to load. Defaults to null
     */
    public function __construct(Db $zdb, ?int $id = null)
    {
        parent::__construct(
            $zdb,
            self::TABLE,
            self::PK,
            self::FIELD,
            self::NAME,
            $id
        );
    }

    /**
     * Get field label
     */
    public function getFieldLabel(): string
    {
        return _T('Transmission', 'auto');
    }

    /**
     * Get property route name
     */
    public function getRouteName(): string
    {
        return 'transmission';
    }

    /**
     * Global getter method
     *
     * @param string $name name of the property we want to retrieve
     *
     * @return mixed the called property
     */
    public function __get(string $name): mixed
    {
        if ($name == self::FIELD) {
            return parent::__get('value');
        } else {
            return parent::__get($name);
        }
    }

    /**
     * Get localized count string for object list
     */
    protected function getLocalizedCount(): string
    {
        return _Tn(
            '%count transmission',
            '%count transmissions',
            $this->getCount(),
            'auto'
        );
    }
}
