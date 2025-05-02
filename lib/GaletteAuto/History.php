<?php

/**
 * Copyright © 2003-2025 The Galette Team
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
use ArrayObject;
use Galette\Core\Db;
use Galette\Entity\Adherent;

/**
 * Automobile History class for galette Auto plugin
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 *
 * @property integer $id_car
 * @property array $fields
 * @property array $entries
 */
class History
{
    public const TABLE = 'history';

    private Db $zdb;

    //fields list and type
    private array $fields = [
        Auto::PK            => 'integer',
        Adherent::PK        => 'integer',
        'history_date'      => 'datetime',
        'car_registration'  => 'text',
        Color::PK           => 'integer',
        State::PK           => 'integer'
    ];

    /**
     * history entries
     *
     * @var array<int, array<string,mixed>> $entries
     */
    private array $entries;
    private int $id_car;

    /**
     * Default constructor
     *
     * @param Db       $zdb Database instance
     * @param ?integer $id  history entry's id to load. Defaults to null
     */
    public function __construct(Db $zdb, ?int $id = null)
    {
        $this->zdb = $zdb;
        if ($id !== null) {
            $this->load($id);
        }
    }

    /**
     * Loads history for specified car
     *
     * @param integer $id car's id we want history for
     *
     * @return boolean
     */
    public function load(int $id): bool
    {
        $this->id_car = $id;

        try {
            $select = $this->zdb->select(AUTO_PREFIX . self::TABLE);
            $select->where(
                [
                    Auto::PK => $id
                ]
            )->order('history_date ASC');

            $results = $this->zdb->execute($select);
            $this->formatEntries($results->toArray());
            return true;
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot get car\'s history (id was ' .
                $this->id_car . ') | ' . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Get the most recent history entry
     *
     * @return ArrayObject|false row
     */
    public function getLatest(): ArrayObject|false
    {
        try {
            $select = $this->zdb->select(AUTO_PREFIX . self::TABLE);
            $select->where(
                [
                    Auto::PK => $this->id_car
                ]
            )->order('history_date DESC')->limit(1);

            $results = $this->zdb->execute($select);
            if ($results->count() > 0) {
                return $results->current();
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) .
                '] Cannot get car\'s latest history entry | ' .
                $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Format entries dates, also loads Member
     *
     * @param array<int, array<string,mixed>> $entries list of entries to format
     *
     * @return void
     */
    private function formatEntries(array $entries): void
    {
        $this->entries = [];
        foreach ($entries as $entry) {
            //put a formatted date to show
            $date = new \DateTime($entry['history_date']);
            $entry['formatted_date'] = $date->format(__('Y-m-d'));

            //associate member to current history entry
            $entry['owner'] = new Adherent($this->zdb, (int)$entry['id_adh']);

            //associate color
            $color = new Color($this->zdb, (int)$entry['id_color']);
            $entry['color'] = $color->value;

            //associate state
            $state = new State($this->zdb, (int)$entry['id_state']);
            $entry['state'] = $state->value;

            $this->entries[] = $entry;
        }
    }

    /**
     * Register a new history entry.
     *
     * @param array $props list of properties to update
     *
     * @return void
     */
    public function register(array $props): void
    {
        Analog::log(
            '[' . get_class($this) . '] Trying to register a new history entry.',
            Analog::DEBUG
        );

        try {
            $fields = $this->fields;
            ksort($fields);
            ksort($props);

            $values = [];
            foreach ($props as $key => $prop) {
                $values[$key] = $prop;
            }

            $insert = $this->zdb->insert(AUTO_PREFIX . self::TABLE);
            $insert->values($values);
            $add = $this->zdb->execute($insert);

            if ($add->count() > 0) {
                Analog::log(
                    '[' . get_class($this) .
                    '] new AutoHistory entry set successfully.',
                    Analog::DEBUG
                );
            } else {
                throw new \Exception(
                    'An error occurred registering car new history entry :('
                );
            }
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot register new history entry | ' .
                $e->getMessage(),
                Analog::ERROR
            );
            throw $e;
        }
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
        switch ($name) {
            case Auto::PK:
                return $this->$name;
            case 'fields':
                return array_keys($this->fields);
        }

        throw new \RuntimeException(
            sprintf(
                'Unable to get property "%s::%s"!',
                __CLASS__,
                $name
            )
        );
    }

    /**
     * Get current car history entries
     *
     * @return array<int, array<string,mixed>>
     */
    public function getEntries(): array
    {
        return $this->entries;
    }
}
