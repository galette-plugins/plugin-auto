<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
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
 */
class History
{
    public const string TABLE = 'history';

    /**
     * Tracked fields; any change on one of them adds an entry
     *
     * @var array<string>
     */
    private const array FIELDS = [
        Auto::PK,
        Adherent::PK,
        'history_date',
        'car_registration',
        Color::PK,
        State::PK
    ];

    private Db $zdb;

    /**
     * history entries
     *
     * @var array<int, array<string,mixed>> $entries
     */
    private array $entries = [];
    private ?int $id_car = null;

    /**
     * Default constructor
     *
     * @param Db   $zdb Database instance
     * @param ?int $id  history entry's id to load. Defaults to null
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
     * @param int $id car's id we want history for
     */
    public function load(int $id): bool
    {
        $this->id_car = $id;

        try {
            $select = $this->zdb->select(AUTO_PREFIX . self::TABLE, 'h');
            $select->join(
                ['c' => PREFIX_DB . AUTO_PREFIX . Color::TABLE],
                'h.' . Color::PK . ' = c.' . Color::PK,
                [Color::FIELD]
            )->join(
                ['s' => PREFIX_DB . AUTO_PREFIX . State::TABLE],
                'h.' . State::PK . ' = s.' . State::PK,
                [State::FIELD]
            )->where(
                [
                    'h.' . Auto::PK => $id
                ]
            )->order('h.history_date ASC');

            $results = $this->zdb->execute($select);
            $this->formatEntries($results->toArray());
            return true;
        } catch (\Throwable $e) {
            Analog::log(
                '[' . static::class . '] Cannot load history of vehicle #' . $this->id_car . ' | ' . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Get the most recent history entry
     *
     * @return ArrayObject<string, mixed>|false row
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
        } catch (\Throwable $e) {
            Analog::log(
                '[' . static::class . '] Cannot load latest history entry of vehicle #' . $this->id_car
                . ' | ' . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Format entries dates, also loads Member
     *
     * @param array<int, array<string,mixed>> $entries list of entries to format
     */
    private function formatEntries(array $entries): void
    {
        $this->entries = [];
        $owners = [];
        foreach ($entries as $entry) {
            //put a formatted date to show
            $date = new \DateTime($entry['history_date']);
            $entry['formatted_date'] = $date->format(__('Y-m-d'));

            //associate member to current history entry, once per member
            $id_adh = (int)$entry[Adherent::PK];
            if (!isset($owners[$id_adh])) {
                $owner = new Adherent($this->zdb);
                $owner->disableAllDeps()->load($id_adh);
                $owners[$id_adh] = $owner;
            }
            $entry['owner'] = $owners[$id_adh];

            $this->entries[] = $entry;
        }
    }

    /**
     * Register a new history entry.
     *
     * @param array<string,mixed> $props list of properties to update
     */
    public function register(array $props): void
    {
        try {
            $insert = $this->zdb->insert(AUTO_PREFIX . self::TABLE);
            $insert->values(array_intersect_key($props, array_flip(self::FIELDS)));
            $add = $this->zdb->execute($insert);

            if ($add->count() === 0) {
                throw new \RuntimeException(
                    'An error occurred registering car new history entry :('
                );
            }
        } catch (\Throwable $e) {
            Analog::log(
                '[' . static::class . '] Cannot add history entry of vehicle #' . ($props[Auto::PK] ?? '')
                . ' | ' . $e->getMessage(),
                Analog::ERROR
            );
            throw $e;
        }
    }

    /**
     * Get car ID
     */
    public function getCarId(): ?int
    {
        return $this->id_car;
    }

    /**
     * Get tracked fields
     *
     * @return array<string>
     */
    public function getFields(): array
    {
        return self::FIELDS;
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
