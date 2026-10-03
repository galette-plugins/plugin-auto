<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\Repository;

use Galette\Core\Db;
use Galette\Core\Preferences;
use Galette\Core\Login;
use GaletteAuto\Model;
use GaletteAuto\Brand;
use GaletteAuto\Filters\ModelsList;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Select;

/**
 * Models repository management
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */

class Models extends AbstractRepository
{
    protected const string ALIAS = 'm';

    /**
     * Main constructor
     *
     * @param Db          $zdb         Database instance
     * @param Preferences $preferences Galette preferences
     * @param Login       $login       Logged in instance
     * @param ModelsList  $filters     Filters
     */
    public function __construct(Db $zdb, Preferences $preferences, Login $login, ModelsList $filters)
    {
        parent::__construct($zdb, $preferences, $login, 'Model', $filters);
    }

    /**
     * Get the list of all models
     *
     * @param ?int $brandId   Optional brand we want models for; the whole list is retrieved then
     * @param bool $as_object Whether to return an array of objects or a ResultSet
     *
     * @return ($as_object is true ? array<int, Model> : ResultSet)
     */
    public function getList(?int $brandId = null, bool $as_object = true): array|ResultSet
    {
        $select = $this->buildSelect();
        if ($brandId !== null) {
            $select->where(['m.' . Brand::PK => $brandId]);
        }
        $results = $this->fetchRows($select, $brandId === null);

        if (!$as_object) {
            return $results;
        }

        $models = [];
        foreach ($results as $r) {
            $models[(int)$r[Model::PK]] = new Model($this->zdb, $r);
        }
        return $models;
    }

    /**
     * Get table name, without prefixes
     */
    protected function getTable(): string
    {
        return Model::TABLE;
    }

    /**
     * Get primary key name
     */
    protected function getPk(): string
    {
        return Model::PK;
    }

    /**
     * Builds the SELECT statement
     */
    protected function buildSelect(): Select
    {
        $select = parent::buildSelect();
        $select->join(
            ['b' => PREFIX_DB . AUTO_PREFIX . Brand::TABLE],
            'm.' . Brand::PK . ' = b.' . Brand::PK
        );
        return $select;
    }

    /**
     * Builds the order clause
     *
     * @return array<string> SQL ORDER clause
     */
    protected function buildOrderClause(): array
    {
        $order = [];

        switch ($this->filters->orderby) {
            case ModelsList::ORDERBY_BRAND:
                $order[] = 'b.brand ' . $this->filters->getDirection();
                break;
            default:
            case ModelsList::ORDERBY_MODEL:
                $order[] = 'm.model ' . $this->filters->getDirection();
                break;
        }
        $order[] = 'm.' . Model::PK . ' ASC';

        return $order;
    }
}
