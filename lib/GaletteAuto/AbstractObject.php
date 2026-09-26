<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto;

use ArrayObject;
use Analog\Analog;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Slim\Routing\RouteParser;
use Galette\Core\Db;
use GaletteAuto\Filters\PropertiesList;

/**
 * Automobile Object abstract class for galette Auto plugin
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
abstract class AbstractObject
{
    public const string TABLE = '';
    public const string PK = '';
    public const string FIELD = '';
    /** Name of the list route */
    public const string LIST_ROUTE = '';

    /**
     * Properties classes, by route property name
     *
     * @var array<string, class-string<AbstractObject>>
     */
    private const array CLASSES = [
        Body::FIELD => Body::class,
        Brand::FIELD => Brand::class,
        Color::FIELD => Color::class,
        Finition::FIELD => Finition::class,
        State::FIELD => State::class,
        Transmission::FIELD => Transmission::class,
    ];

    protected Db $zdb;
    protected ?int $id = null;
    protected ?string $value = null;
    protected ?PropertiesList $filters = null;

    private int $count;

    /**
     * Default constructor
     *
     * @param Db   $zdb Database instance
     * @param ?int $id  id to load. Defaults to null
     */
    final public function __construct(Db $zdb, ?int $id = null)
    {
        $this->zdb = $zdb;
        if (is_int($id)) {
            $this->load($id);
        }
    }

    /**
     * Get a property instance from its route name
     *
     * @param Db     $zdb      Database instance
     * @param string $property Route property name
     */
    public static function fromPropertyName(Db $zdb, string $property): self
    {
        $class = self::getClassForPropName($property);
        return new $class($zdb);
    }

    /**
     * Get the list
     *
     * @return array<int, ArrayObject<string, mixed>>
     */
    public function getList(): array
    {
        try {
            $select = $this->buildSelect();
            $results = $this->zdb->execute($select);
            $list = [];
            foreach ($results as $row) {
                $list[] = $row;
            }
            return $list;
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot load ' . static::TABLE
                . ' list | ' . $e->getMessage(),
                Analog::ERROR
            );
            throw $e;
        }
    }

    /**
     * Loads a record
     *
     * @param int $id id of the record
     */
    public function load(int $id): bool
    {
        try {
            $select = $this->zdb->select(AUTO_PREFIX . static::TABLE);
            $select->where(
                [
                    static::PK => $id
                ]
            );

            $result = $this->zdb->execute($select)->current();
            if (!$result instanceof ArrayObject) {
                throw new \RuntimeException('Record not found');
            }
            $this->loadFromRow($result);

            return true;
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot load ' . static::TABLE
                . ' from id `' . $id . '` | ' . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Populate from a resultset row, which may come from a join
     *
     * @param ArrayObject<string, mixed> $row Resultset row
     */
    public function loadFromRow(ArrayObject $row): self
    {
        $this->id = (int)$row[static::PK];
        $this->value = (string)$row[static::FIELD];
        return $this;
    }

    /**
     * Store current record
     *
     * @param bool $new New record or existing one
     */
    public function store(bool $new = false): bool
    {
        try {
            $values = [
                static::FIELD => $this->value
            ];
            if ($new) {
                $insert = $this->zdb->insert(AUTO_PREFIX . static::TABLE);
                $insert->values($values);
                $this->zdb->execute($insert);
                /** @phpstan-ignore-next-line */
                $this->id = (int)$this->zdb->driver->getLastGeneratedValue(
                    $this->zdb->isPostgres()
                        ? PREFIX_DB . AUTO_PREFIX . static::TABLE . '_id_seq'
                        : null
                );
            } else {
                $update = $this->zdb->update(AUTO_PREFIX . static::TABLE);
                $update->set($values)->where(
                    [
                        static::PK => $this->id
                    ]
                );
                $this->zdb->execute($update);
            }
            return true;
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot store ' . static::TABLE
                . ' values `' . ($this->id ?? '') . '`, `' . $this->value . '` | '
                . $e->getMessage(),
                Analog::WARNING
            );
            return false;
        }
    }

    /**
     * Delete some records
     *
     * @param int[] $ids Array of records id to delete
     */
    public function delete(array $ids): bool
    {
        try {
            $delete = $this->zdb->delete(AUTO_PREFIX . static::TABLE);
            $delete->where->in(static::PK, $ids);
            $this->zdb->execute($delete);
            return true;
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot delete ' . static::TABLE
                . ' from ids `' . implode(' - ', $ids) . '` | ' . $e->getMessage(),
                Analog::WARNING
            );
            throw $e;
        }
    }

    /**
     * Set filters
     *
     * @param PropertiesList $filters Filters
     */
    public function setFilters(PropertiesList $filters): self
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Get field label
     */
    abstract public function getFieldLabel(): string;

    /**
     * Get list page title
     */
    abstract public function getListTitle(): string;

    /**
     * Get add button text
     */
    abstract public function getAddText(): string;

    /**
     * Get localized count
     *
     * @param int $count Count
     */
    abstract public function getCountLabel(int $count): string;

    /**
     * Get removal success message
     *
     * @param int $count Removed records count
     */
    abstract public function getRemovedMessage(int $count): string;

    /**
     * Get message when removal is refused because the record is in use
     */
    abstract public function getInUseMessage(): string;

    /**
     * Get removal error message
     */
    abstract public function getRemoveErrorMessage(): string;

    /**
     * Whether records have a details page
     */
    public function hasDetails(): bool
    {
        return false;
    }

    /**
     * Get property route name
     */
    public function getRouteName(): string
    {
        return static::FIELD;
    }

    /**
     * Get record ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get record value
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Set record value
     *
     * @param string $value Value
     */
    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Get primary key field name
     */
    public function getPk(): string
    {
        return static::PK;
    }

    /**
     * Get value field name
     */
    public function getField(): string
    {
        return static::FIELD;
    }

    /**
     * Get list route
     *
     * @param RouteParser $routeparser Route parser instance
     */
    public static function getListRoute(RouteParser $routeparser): string
    {
        return $routeparser->urlFor(static::LIST_ROUTE);
    }

    /**
     * Get object class name from route property
     *
     * @param string $property Route property
     *
     * @return class-string<AbstractObject>
     */
    public static function getClassForPropName(string $property): string
    {
        if (!isset(self::CLASSES[$property])) {
            throw new \RuntimeException('Unknown property ' . $property);
        }
        return self::CLASSES[$property];
    }

    /**
     * Builds the SELECT statement
     *
     * @return Select SELECT statement
     */
    private function buildSelect(): Select
    {
        try {
            $select = $this->zdb->select(AUTO_PREFIX . static::TABLE);
            $select->order([static::FIELD . ' ASC', static::PK . ' ASC']);
            if (isset($this->filters)) {
                $this->filters->setLimits($select);
            }
            $this->proceedCount($select);

            return $select;
        } catch (\Exception $e) {
            Analog::log(
                'Cannot build SELECT clause | ' . $e->getMessage(),
                Analog::WARNING
            );
            throw $e;
        }
    }

    /**
     * Count objects from the query
     *
     * @param Select $select Original select
     */
    private function proceedCount(Select $select): void
    {
        try {
            $countSelect = clone $select;
            $countSelect->reset($countSelect::COLUMNS);
            $countSelect->reset($countSelect::JOINS);
            $countSelect->reset($countSelect::ORDER);
            $countSelect->reset($countSelect::LIMIT);
            $countSelect->reset($countSelect::OFFSET);
            $countSelect->columns(
                [
                    static::PK => new Expression('COUNT(' . static::PK . ')')
                ]
            );

            $results = $this->zdb->execute($countSelect);
            $result = $results->current();

            $k = static::PK;
            $this->count = (int)$result->$k;

            if ($this->count > 0 && isset($this->filters)) {
                $this->filters->setCounter($this->count);
            }
        } catch (\Exception $e) {
            Analog::log(
                'Cannot count models | ' . $e->getMessage(),
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

    /**
     * Display localized count for object
     */
    public function displayCount(): string
    {
        return $this->getCountLabel($this->getCount());
    }
}
