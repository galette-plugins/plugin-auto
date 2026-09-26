<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\Repository;

use Galette\Core\Db;
use Galette\Core\Login;
use Galette\Core\Preferences;
use GaletteAuto\AbstractObject;
use GaletteAuto\Filters\PropertiesList;

/**
 * Vehicle properties repository: brands, colors, states...
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Properties extends AbstractRepository
{
    protected const string ALIAS = 'p';

    private bool $paginate;

    /**
     * Constructor
     *
     * @param Db                           $zdb         Database instance
     * @param Preferences                  $preferences Preferences instance
     * @param Login                        $login       Logged in instance
     * @param class-string<AbstractObject> $class       Property class name
     * @param ?PropertiesList              $filters     Filters; the whole list is retrieved without them
     */
    public function __construct(
        Db $zdb,
        Preferences $preferences,
        Login $login,
        private string $class,
        ?PropertiesList $filters = null
    ) {
        $this->paginate = $filters !== null;
        parent::__construct(
            $zdb,
            $preferences,
            $login,
            substr($class, strrpos($class, '\\') + 1),
            $filters ?? new PropertiesList()
        );
    }

    /**
     * Get the list, sorted by value
     *
     * @return array<int, AbstractObject>
     */
    public function getList(): array
    {
        $list = [];
        foreach ($this->fetchRows($this->buildSelect(), $this->paginate) as $row) {
            $list[] = (new $this->class($this->zdb))->loadFromRow($row);
        }
        return $list;
    }

    /**
     * Get table name, without prefixes
     */
    protected function getTable(): string
    {
        return $this->class::TABLE;
    }

    /**
     * Get primary key name
     */
    protected function getPk(): string
    {
        return $this->class::PK;
    }

    /**
     * Builds the order clause
     *
     * @return array<string>
     */
    protected function buildOrderClause(): array
    {
        return [
            self::ALIAS . '.' . $this->class::FIELD . ' ASC',
            self::ALIAS . '.' . $this->class::PK . ' ASC'
        ];
    }
}
