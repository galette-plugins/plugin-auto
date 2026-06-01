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
 * Automobile Colors class for galette Auto plugin
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Color extends AbstractObject
{
    public const string TABLE = 'colors';
    public const string PK = 'id_color';
    public const string FIELD = 'color';
    public const string NAME = 'colors';

    /**
     * Default constructor
     *
     * @param Db   $zdb Database instance
     * @param ?int $id  state's id to load. Defaults to null
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
        return _T('Color', 'auto');
    }

    /**
     * Get property route name
     */
    public function getRouteName(): string
    {
        return 'color';
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
            '%count color',
            '%count colors',
            $this->getCount(),
            'auto'
        );
    }
}
