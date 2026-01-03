<?php

/**
 * Copyright © 2003-2026 The Galette Team
 *
 * This file is part of Galette (https://galette.eu).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
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
    public const TABLE = 'transmissions';
    public const PK = 'id_transmission';
    public const FIELD = 'transmission';
    public const NAME = 'transmissions';

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
