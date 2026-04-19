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

use Analog\Analog;
use Galette\Core\Db;
use Galette\Core\Plugins;
use Galette\Entity\Adherent;
use GaletteAuto\Filters\AutosList;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

/**
 * Automobile autos class for galette Auto plugin
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Autos
{
    public const string TABLE = Auto::TABLE;
    public const string PK = Auto::PK;

    private Plugins $plugins;
    private Db $zdb;
    private ?int $count = null;

    /**
     * Constructor
     *
     * @param Plugins $plugins Plugins instance
     * @param Db      $zdb     Database instance
     */
    public function __construct(Plugins $plugins, Db $zdb)
    {
        $this->plugins = $plugins;
        $this->zdb = $zdb;
    }

    /**
     * Remove specified vehicles
     *
     * @param int|array<int> $ids Vehicles identifiers to delete
     */
    public function removeVehicles(int|array $ids): bool
    {
        global $hist;

        $list = is_array($ids) ? $ids : [$ids];

        try {
            $this->zdb->connection->beginTransaction();

            //Retrieve some information
            $select = $this->zdb->select(AUTO_PREFIX . self::TABLE, 'a');
            $select->columns(
                [
                    self::PK,
                    'car_name'
                ]
            )->join(
                ['b' => PREFIX_DB . AUTO_PREFIX . Model::TABLE],
                'a.' . Model::PK . ' = b.' . Model::PK,
                ['model']
            )->join(
                ['c' => PREFIX_DB . AUTO_PREFIX . Brand::TABLE],
                'b.' . Brand::PK . ' = c.' . Brand::PK,
                ['brand']
            )->where->in(self::PK, $list);

            $vehicles = $this->zdb->execute($select);

            $infos = null;
            foreach ($vehicles as $vehicle) {
                $str_v = $vehicle->id_car . ' - ' . $vehicle->car_name
                    . ' (' . $vehicle->brand . ' ' . $vehicle->model . ')';
                $infos .= $str_v . "\n";

                $p = new Picture($this->plugins, $vehicle->id_car);
                if ($p->hasPicture()) {
                    if (!$p->delete()) {
                        Analog::log(
                            'Unable to delete picture for vehicle '
                            . $str_v,
                            Analog::ERROR
                        );
                        throw new \Exception(
                            'Unable to delete picture for vehicle '
                            . $str_v
                        );
                    } else {
                        $hist->add(
                            "Vehicle Picture deleted",
                            $str_v
                        );
                    }
                }
            }

            //delete vehicles history
            $delete = $this->zdb->delete(AUTO_PREFIX . History::TABLE);
            $delete->where->in(self::PK, $list);
            $this->zdb->execute($delete);

            //delete vehicles
            $delete = $this->zdb->delete(AUTO_PREFIX . self::TABLE);
            $delete->where->in(self::PK, $list);
            $this->zdb->execute($delete);

            //add a history entry
            $hist->add(
                _T("Delete vehicles cards", "auto"),
                $infos
            );

            //commit all changes
            $this->zdb->connection->commit();
            return true;
        } catch (\Exception $e) {
            $this->zdb->connection->rollBack();
            Analog::log(
                'Unable to delete selected vehicle(s) |'
                . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Get vehicles list for specified member
     *
     * @param int        $id_adh  Members id
     * @param ?AutosList $filters Filters
     *
     * @return array<int, Autos> Vehicles list
     */
    public function getMemberList(int $id_adh, ?AutosList $filters): array
    {
        return $this->getList(true, false, null, $filters, $id_adh);
    }

    /**
     * Get the list of all vehicles
     *
     * @param bool           $as_autos return the results as an array of Auto object.
     *                                 When true, fields are not relevant
     * @param bool           $mine     show only current logged member cars
     * @param ?array<string> $fields   field(s) name(s) to get.
     *                                 or an array. If null, all fields will be returned
     * @param ?AutosList     $filters  Filters
     * @param ?int           $id_adh   Member id
     * @param bool           $public   Get public list
     *
     * @return array<int, Autos>|ResultSet
     */
    public function getList(
        bool $as_autos = false,
        bool $mine = false,
        ?array $fields = null,
        ?AutosList $filters = null,
        ?int $id_adh = null,
        bool $public = false
    ): array|ResultSet {
        global $login;

        $fieldsList = ['*'];
        if (is_array($fields) && count($fields)) {
            $fieldsList = $fields;
        }

        try {
            $select = $this->zdb->select(AUTO_PREFIX . self::TABLE, 'a');
            $select->columns($fieldsList);

            //restrict on user self vehicles when not admin, or if admin and requested 'my vehicles'
            //restrict on public authorized users if public list
            $on_logged = false;
            if ($mine) {
                $on_logged = true;
            } elseif ($public) {
                $members = new \Galette\Repository\Members();
                $allpublic = $members->getPublicList(false);
                $public_members = array_merge($allpublic['members'], $allpublic['staff']);
                if (count($public_members)) {
                    foreach ($public_members as $p) {
                        $adhs[] = $p->id;
                    }
                    $select->where->in(Adherent::PK, $adhs);
                } else {
                    $on_logged = true;
                }
            } elseif (!$login->isAdmin() && !$login->isStaff() && $login->isGroupManager()) {
                $groups = new \Galette\Repository\Groups($this->zdb, $login);
                $managed_users = $groups->getManagerUsers();
                if (count($managed_users)) {
                    $managed_users[] = $login->id;
                    $select->where->in(Adherent::PK, $managed_users);
                } else {
                    $on_logged = true;
                }
            } elseif (!$login->isAdmin() && !$login->isStaff()) {
                $on_logged = true;
            }

            if ($on_logged) {
                $select->where(
                    [
                        Adherent::PK => $login->id
                    ]
                );
            }

            //restrict on specified user vehicles if an id has been provided
            if ($id_adh !== null) {
                $select->where(
                    [
                        Adherent::PK => $id_adh
                    ]
                );
            }

            $this->proceedCount($select, $filters);

            if ($filters !== null) {
                $filters->setLimit($select);
            }

            $results = $this->zdb->execute($select);
            $autos = [];
            if ($as_autos) {
                foreach ($results as $row) {
                    $autos[] = new Auto($this->plugins, $this->zdb, $row);
                }
            } else {
                $autos = $results;
            }
            return $autos;
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot list Autos | '
                . $e->getMessage(),
                Analog::ERROR
            );
            throw $e;
        }
    }

    /**
     * Count vehicles from the query
     *
     * @param Select     $select  Original select
     * @param ?AutosList $filters Filters
     */
    private function proceedCount(Select $select, ?AutosList $filters): void
    {
        try {
            $countSelect = clone $select;
            $countSelect->reset($countSelect::COLUMNS);
            $countSelect->reset($countSelect::ORDER);
            $countSelect->reset($countSelect::HAVING);
            $countSelect->columns(
                [
                    'count' => new Expression('count(DISTINCT a.' . self::PK . ')')
                ]
            );

            $have = $select->having;
            if ($have->count() > 0) {
                foreach ($have->getPredicates() as $h) {
                    $countSelect->where($h);
                }
            }

            $results = $this->zdb->execute($countSelect);
            $this->count = (int)$results->current()->count;
            if ($this->count > 0 && $filters !== null) {
                $filters->setCounter($this->count);
            }
        } catch (\Exception $e) {
            Analog::log(
                'Cannot count vehicles | ' . $e->getMessage(),
                Analog::WARNING
            );
            throw $e;
        }
    }

    /**
     * Get count for list
     */
    public function getCount(): int
    {
        return $this->count;
    }
}
