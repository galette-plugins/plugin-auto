<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\Repository;

use Analog\Analog;
use Galette\Core\Db;
use Galette\Core\Login;
use Galette\Core\Pagination;
use Galette\Core\Preferences;
use Galette\Repository\Repository;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

/**
 * Common code for lists of models and properties
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
abstract class AbstractRepository extends Repository
{
    /** Table alias used in queries */
    protected const string ALIAS = '';

    private int $count = 0;

    /**
     * Constructor
     *
     * @param Db          $zdb         Database instance
     * @param Preferences $preferences Preferences instance
     * @param Login       $login       Logged in instance
     * @param string      $entity      Entity class name, relative to the plugin namespace
     * @param Pagination  $filters     Filtering
     */
    public function __construct(
        Db $zdb,
        Preferences $preferences,
        Login $login,
        string $entity,
        Pagination $filters
    ) {
        parent::__construct($zdb, $preferences, $login, $entity, 'GaletteAuto', AUTO_PREFIX);
        $this->filters = $filters;
    }

    /**
     * Get table name, without prefixes
     */
    abstract protected function getTable(): string;

    /**
     * Get primary key name
     */
    abstract protected function getPk(): string;

    /**
     * Builds the SELECT statement, neither ordered nor limited
     */
    protected function buildSelect(): Select
    {
        return $this->zdb->select(AUTO_PREFIX . $this->getTable(), static::ALIAS);
    }

    /**
     * Builds the order clause
     *
     * @return array<string>
     */
    abstract protected function buildOrderClause(): array;

    /**
     * Get the rows, counting all of them
     *
     * @param Select $select Select, filtered
     * @param bool   $limit  Only retrieve the rows of the current page
     */
    protected function fetchRows(Select $select, bool $limit): ResultSet
    {
        $select->order($this->buildOrderClause());
        $this->proceedCount($select);
        if ($limit) {
            $this->filters->setLimits($select);
        }

        /** @var ResultSet $rows */
        $rows = $this->zdb->execute($select);
        return $rows;
    }

    /**
     * Count rows matching the query
     *
     * Counting on a subquery keeps every filter right.
     *
     * @param Select $select Original select
     */
    private function proceedCount(Select $select): void
    {
        $counted = clone $select;
        $counted->reset(Select::COLUMNS);
        $counted->reset(Select::ORDER);
        $counted->reset(Select::JOINS);
        $counted->columns(['id' => new Expression(static::ALIAS . '.' . $this->getPk())]);
        foreach ($select->joins as $join) {
            $counted->join($join['name'], $join['on'], [], $join['type']);
        }

        $count_select = new Select(['counted' => $counted]);
        $count_select->columns(['count' => new Expression('COUNT(*)')]);

        $result = $this->zdb->execute($count_select)->current();
        $this->count = (int)$result['count'];
        $this->filters->setCounter($this->count);
    }

    /**
     * Get count for last list
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Remove records
     *
     * @param array<int> $ids Records IDs
     *
     * @throws \Throwable
     */
    public function remove(array $ids): void
    {
        try {
            $delete = $this->zdb->delete(AUTO_PREFIX . $this->getTable());
            $delete->where->in($this->getPk(), $ids);
            $this->zdb->execute($delete);
        } catch (\Throwable $e) {
            Analog::log(
                '[' . static::class . '] Cannot remove ' . $this->getTable() . ' #' . implode(', #', $ids)
                . ' | ' . $e->getMessage(),
                Analog::ERROR
            );
            throw $e;
        }
    }

    /**
     * Nothing to initialize
     *
     * @param bool $check_first Check first if it seems initialized
     */
    public function installInit(bool $check_first = true): bool
    {
        return true;
    }
}
