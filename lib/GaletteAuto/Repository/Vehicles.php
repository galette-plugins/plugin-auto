<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\Repository;

use Analog\Analog;
use ArrayObject;
use Galette\Core\Db;
use Galette\Core\History as CoreHistory;
use Galette\Core\Login;
use Galette\Core\Plugins;
use Galette\Entity\Adherent;
use Galette\Entity\Status;
use Galette\Repository\Groups;
use GaletteAuto\Auto;
use GaletteAuto\Body;
use GaletteAuto\Brand;
use GaletteAuto\Color;
use GaletteAuto\Filters\AutosList;
use GaletteAuto\Finition;
use GaletteAuto\History;
use GaletteAuto\Model;
use GaletteAuto\Picture;
use GaletteAuto\State;
use GaletteAuto\Transmission;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

/**
 * Vehicles repository
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Vehicles
{
    private int $count = 0;

    /**
     * Constructor
     *
     * @param Plugins     $plugins Plugins instance
     * @param Db          $zdb     Database instance
     * @param Login       $login   Login instance
     * @param CoreHistory $history Galette history, logs changes
     */
    public function __construct(
        private readonly Plugins $plugins,
        private readonly Db $zdb,
        private readonly Login $login,
        private readonly CoreHistory $history
    ) {
    }

    /**
     * Get vehicles, with their properties and owner, in a few queries
     *
     * Without member nor public restriction, vehicles are the ones current
     * user manages: every one for staff, the ones of the members of their
     * groups for group managers, their own ones for others.
     *
     * @param ?AutosList $filters Filters
     * @param ?int       $id_adh  Only vehicles of this member
     * @param bool       $mine    Only current user vehicles
     * @param bool       $public  Public list: every vehicle
     *
     * @return array<int, Auto>
     */
    public function getList(
        ?AutosList $filters = null,
        ?int $id_adh = null,
        bool $mine = false,
        bool $public = false
    ): array {
        $select = $this->buildSelect();

        if ($mine) {
            $select->where(['a.' . Adherent::PK => $this->login->id]);
        } elseif ($id_adh !== null) {
            $select->where(['a.' . Adherent::PK => $id_adh]);
        } elseif (!$public && !$this->login->isAdmin() && !$this->login->isStaff()) {
            $members = [$this->login->id];
            if ($this->login->isGroupManager()) {
                $groups = new Groups($this->zdb, $this->login);
                $members = array_merge($members, $groups->getManagerUsers() ?: []);
            }
            $select->where->in('a.' . Adherent::PK, $members);
        }

        $this->proceedCount($select, $filters);
        $select->order(['a.car_name ASC', 'a.' . Auto::PK . ' ASC']);
        $filters?->setLimits($select);

        $rows = $this->zdb->execute($select)->toArray();
        $owners = $this->loadOwners(array_column($rows, Adherent::PK));

        $vehicles = [];
        foreach ($rows as $row) {
            $vehicle = new Auto($this->plugins, $this->zdb, new ArrayObject($row));
            $id_owner = (int)$row[Adherent::PK];
            if (isset($owners[$id_owner])) {
                $vehicle->setOwnerMember($owners[$id_owner]);
            }
            $vehicles[] = $vehicle;
        }
        return $vehicles;
    }

    /**
     * Get count for last list
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Get vehicles owners
     *
     * @param array<int> $ids Vehicles IDs
     *
     * @return array<int, int> Owners IDs, per vehicle ID; missing vehicles are left out
     */
    public function getOwners(array $ids): array
    {
        if (count($ids) === 0) {
            return [];
        }

        $select = $this->zdb->select(AUTO_PREFIX . Auto::TABLE);
        $select->columns([Auto::PK, Adherent::PK])->where->in(Auto::PK, $ids);
        $owners = [];
        foreach ($this->zdb->execute($select) as $row) {
            $owners[(int)$row[Auto::PK]] = (int)$row[Adherent::PK];
        }
        return $owners;
    }

    /**
     * Store a vehicle, and its history when a tracked value changed
     *
     * @param Auto $vehicle Vehicle
     *
     * @throws \Throwable
     */
    public function store(Auto $vehicle): void
    {
        $new = $vehicle->getId() === null;
        $transaction = !$this->zdb->inTransaction();

        try {
            if ($transaction) {
                $this->zdb->beginTransaction();
            }

            if ($new) {
                $vehicle->setCreationDate(date('Y-m-d'));
                $insert = $this->zdb->insert(AUTO_PREFIX . Auto::TABLE);
                $insert->values($vehicle->getStorableValues());
                $this->zdb->execute($insert);
                /** @phpstan-ignore-next-line */
                $vehicle->setId((int)$this->zdb->driver->getLastGeneratedValue(
                    $this->zdb->isPostgres()
                        ? PREFIX_DB . AUTO_PREFIX . Auto::TABLE . '_id_seq'
                        : null
                ));
                $this->history->add(
                    _T("New car added", "auto"),
                    strtoupper((string)$vehicle->getName())
                );
            } else {
                $update = $this->zdb->update(AUTO_PREFIX . Auto::TABLE);
                $update->set($vehicle->getStorableValues())->where([Auto::PK => $vehicle->getId()]);
                //no affected rows does not mean an error, but nothing to change
                if ($this->zdb->execute($update)->count() > 0) {
                    $this->history->add(
                        _T("Car updated", "auto"),
                        strtoupper((string)$vehicle->getName())
                    );
                }
            }

            $history = $vehicle->getHistory();
            $current = $vehicle->getHistoryValues();
            $latest = $new ? false : $history->getLatest();
            $changed = $new;
            if ($latest !== false) {
                foreach ($current as $key => $value) {
                    if ($key !== 'history_date' && (string)$latest[$key] !== (string)$value) {
                        $changed = true;
                        break;
                    }
                }
            }
            if ($changed) {
                $history->register($current);
                $history->load((int)$vehicle->getId());
            }

            if ($transaction) {
                $this->zdb->commit();
            }
        } catch (\Throwable $e) {
            if ($transaction) {
                $this->zdb->rollback();
            }
            Analog::log(
                '[' . static::class . '] Cannot ' . ($new ? 'add' : 'update') . ' vehicle #' . ($vehicle->getId() ?? '')
                . ' | ' . $e->getMessage(),
                Analog::ERROR
            );
            throw $e;
        }
    }

    /**
     * Remove vehicles, with their history and photos
     *
     * @param array<int> $ids Vehicles IDs
     *
     * @throws \Throwable
     */
    public function remove(array $ids): void
    {
        $transaction = !$this->zdb->inTransaction();

        try {
            if ($transaction) {
                $this->zdb->beginTransaction();
            }

            $select = $this->zdb->select(AUTO_PREFIX . Auto::TABLE, 'a');
            $select->columns([Auto::PK, 'car_name'])->join(
                ['m' => PREFIX_DB . AUTO_PREFIX . Model::TABLE],
                'a.' . Model::PK . ' = m.' . Model::PK,
                [Model::FIELD]
            )->join(
                ['b' => PREFIX_DB . AUTO_PREFIX . Brand::TABLE],
                'm.' . Brand::PK . ' = b.' . Brand::PK,
                [Brand::FIELD]
            )->where->in('a.' . Auto::PK, $ids);

            $infos = '';
            foreach ($this->zdb->execute($select) as $vehicle) {
                $str_v = $vehicle[Auto::PK] . ' - ' . $vehicle['car_name']
                    . ' (' . $vehicle[Brand::FIELD] . ' ' . $vehicle[Model::FIELD] . ')';
                $infos .= $str_v . "\n";

                $picture = new Picture($this->plugins, (int)$vehicle[Auto::PK]);
                if ($picture->hasPicture()) {
                    //file may be gone if the transaction is rolled back: it
                    //will be written again from the database when displayed
                    if (!$picture->delete(false)) {
                        throw new \RuntimeException('Unable to delete picture for vehicle ' . $str_v);
                    }
                    $this->history->add(
                        _T("Vehicle picture deleted", "auto"),
                        $str_v
                    );
                }
            }

            $delete = $this->zdb->delete(AUTO_PREFIX . History::TABLE);
            $delete->where->in(Auto::PK, $ids);
            $this->zdb->execute($delete);

            $delete = $this->zdb->delete(AUTO_PREFIX . Auto::TABLE);
            $delete->where->in(Auto::PK, $ids);
            $this->zdb->execute($delete);

            $this->history->add(
                _T("Delete vehicles cards", "auto"),
                $infos
            );

            if ($transaction) {
                $this->zdb->commit();
            }
        } catch (\Throwable $e) {
            if ($transaction) {
                $this->zdb->rollback();
            }
            Analog::log(
                '[' . static::class . '] Cannot remove vehicles #' . implode(', #', $ids) . ' | ' . $e->getMessage(),
                Analog::ERROR
            );
            throw $e;
        }
    }

    /**
     * Build vehicles select, joining their properties
     */
    private function buildSelect(): Select
    {
        $select = $this->zdb->select(AUTO_PREFIX . Auto::TABLE, 'a');
        $joins = [
            'co' => [Color::TABLE, Color::PK, [Color::FIELD]],
            'bo' => [Body::TABLE, Body::PK, [Body::FIELD]],
            'st' => [State::TABLE, State::PK, [State::FIELD]],
            'tr' => [Transmission::TABLE, Transmission::PK, [Transmission::FIELD]],
            'fi' => [Finition::TABLE, Finition::PK, [Finition::FIELD]],
            'mo' => [Model::TABLE, Model::PK, [Model::FIELD, Brand::PK]],
        ];
        foreach ($joins as $alias => [$table, $pk, $columns]) {
            $select->join(
                [$alias => PREFIX_DB . AUTO_PREFIX . $table],
                'a.' . $pk . ' = ' . $alias . '.' . $pk,
                $columns
            );
        }
        $select->join(
            ['br' => PREFIX_DB . AUTO_PREFIX . Brand::TABLE],
            'mo.' . Brand::PK . ' = br.' . Brand::PK,
            [Brand::FIELD]
        );
        return $select;
    }

    /**
     * Load owners, in one query
     *
     * @param array<int|string> $ids Members IDs
     *
     * @return array<int, Adherent>
     */
    private function loadOwners(array $ids): array
    {
        $ids = array_unique(array_map('intval', $ids));
        if (count($ids) === 0) {
            return [];
        }

        $select = $this->zdb->select(Adherent::TABLE, 'a');
        $select->join(
            ['s' => PREFIX_DB . Status::TABLE],
            'a.' . Status::PK . ' = s.' . Status::PK,
            ['priorite_statut']
        )->where->in('a.' . Adherent::PK, $ids);

        $owners = [];
        foreach ($this->zdb->execute($select) as $row) {
            $owners[(int)$row[Adherent::PK]] = new Adherent($this->zdb, $row, false);
        }
        return $owners;
    }

    /**
     * Count vehicles from the query
     *
     * @param Select     $select  Original select
     * @param ?AutosList $filters Filters
     */
    private function proceedCount(Select $select, ?AutosList $filters): void
    {
        $countSelect = clone $select;
        $countSelect->reset($countSelect::COLUMNS);
        $countSelect->reset($countSelect::JOINS);
        $countSelect->reset($countSelect::ORDER);
        $countSelect->columns(
            [
                'count' => new Expression('count(DISTINCT a.' . Auto::PK . ')')
            ]
        );

        $this->count = (int)$this->zdb->execute($countSelect)->current()['count'];
        if ($this->count > 0 && $filters !== null) {
            $filters->setCounter($this->count);
        }
    }
}
