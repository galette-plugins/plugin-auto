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
 * Automobile Brands class for galette Auto plugin
 *
 * @category  Plugins
 * @name      AutoBrands
 * @author Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2009-2014 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      https://galette.eu
 * @since     Available since 0.7dev - 2009-03-16
 *
 * @property int $id
 */
class Brand extends AbstractObject
{
    public const TABLE = 'brands';
    public const PK = 'id_brand';
    public const FIELD = 'brand';
    public const NAME = 'brands';

    /**
     * Default constructor
     *
     * @param Db   $zdb Database instance
     * @param ?int $id  brand's id to load. Defaults to null
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
        return _T('Brand', 'auto');
    }

    /**
     * Get property route name
     */
    public function getRouteName(): string
    {
        return 'brand';
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
        }
        if ($name == self::PK) {
            return parent::__get('id');
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
            '%count brand',
            '%count brands',
            $this->getCount(),
            'auto'
        );
    }
}
