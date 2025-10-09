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
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Slim\Routing\RouteParser;
use Galette\Core\Db;
use GaletteAuto\Filters\PropertiesList;

/**
 * Automobile Object abstract class for galette Auto plugin
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 *
 * @property int $id
 * @property string $value
 */
abstract class AbstractObject
{
    private string $table;
    private string $pk;
    private string $field;
    private string $name;

    protected Db $zdb;
    protected ?int $id;
    protected ?string $value;
    protected ?PropertiesList $filters = null;

    private int $count;

    /**
     * Default constructor
     *
     * @param Db       $zdb   Database instance
     * @param string   $table table name
     * @param string   $pk    primary key field
     * @param string   $field main field name
     * @param string   $name  name
     * @param ?integer $id    id to load. Defaults to null
     */
    public function __construct(Db $zdb, string $table, string $pk, string $field, string $name, ?int $id = null)
    {
        $this->zdb = $zdb;
        $this->table = AUTO_PREFIX . $table;
        $this->pk = $pk;
        $this->field = $field;
        $this->name = $name;
        if (is_int($id)) {
            $this->load($id);
        }
    }

    /**
     * Get the list
     *
     * @return array
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
                '[' . get_class($this) . '] Cannot load ' . $this->name
                . ' list | ' . $e->getMessage(),
                Analog::ERROR
            );
            throw $e;
        }
    }

    /**
     * Loads a record
     *
     * @param integer $id id of the record
     *
     * @return boolean
     */
    public function load(int $id): bool
    {
        try {
            $select = $this->zdb->select($this->table);
            $select->where(
                [
                    $this->pk => $id
                ]
            );

            $results = $this->zdb->execute($select);
            $result = $results->current();
            $pk = $this->pk;
            $this->id = (int)$result->$pk;
            $field = $this->field;
            $this->value = $result->$field;

            return true;
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot load ' . $this->name
                . ' from id `' . $id . '` | ' . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Store current record
     *
     * @param boolean $new New record or existing one
     *
     * @return boolean
     */
    public function store(bool $new = false): bool
    {
        try {
            $values = [
                $this->field => $this->value
            ];
            if ($new) {
                $insert = $this->zdb->insert($this->table);
                $insert->values($values);
                $this->zdb->execute($insert);
                /** @phpstan-ignore-next-line */
                $this->id = (int)$this->zdb->driver->getLastGeneratedValue(
                    $this->zdb->isPostgres()
                        ? PREFIX_DB . $this->table . '_id_seq'
                        : null
                );
            } else {
                $update = $this->zdb->update($this->table);
                $update->set($values)->where(
                    [
                        $this->pk => $this->id
                    ]
                );
                $this->zdb->execute($update);
            }
            return true;
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot store ' . $this->name
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
     *
     * @return boolean
     */
    public function delete(array $ids): bool
    {
        try {
            $delete = $this->zdb->delete($this->table);
            $delete->where->in($this->pk, $ids);
            $this->zdb->execute($delete);
            return true;
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot delete ' . $this->name
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
     *
     * @return self
     */
    public function setFilters(PropertiesList $filters): self
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Get field label
     *
     * @return string
     */
    abstract public function getFieldLabel(): string;

    /**
     * Get property route name
     *
     * @return string
     */
    abstract public function getRouteName(): string;

    /**
     * Global getter method
     *
     * @param string $name name of the property we want to retrieve
     *
     * @return mixed the called property
     */
    public function __get(string $name): mixed
    {
        if (property_exists($this, $name)) {
            return $this->$name ?? null;
        } else {
            Analog::log(
                '[' . get_class($this) . '] Unable to retrieve `' . $name . '`',
                Analog::INFO
            );
            throw new \RuntimeException('Unable to retrieve `' . $name . '`');
        }
    }

    /**
     * Global isset method
     * Required for twig to access properties via __get
     *
     * @param string $name name of the property we want to retrieve
     *
     * @return boolean
     */
    public function __isset(string $name): bool
    {
        return property_exists($this, $name);
    }

    /**
     * Global setter method
     *
     * @param string $name  name of the property we want to assign a value to
     * @param mixed  $value a relevant value for the property
     *
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        switch ($name) {
            case 'value':
                $this->value = $value;
                break;
        }
    }

    /**
     * Get list route
     *
     * @param RouteParser $routeparser Route parser instance
     * @param string      $property    Property name
     *
     * @return string
     */
    public static function getListRoute(RouteParser $routeparser, string $property): string
    {
        $route = null;
        switch ($property) {
            case 'color':
                $route = $routeparser->urlFor('colorsList');
                break;
            case 'state':
                $route = $routeparser->urlFor('statesList');
                break;
            case 'finition':
                $route = $routeparser->urlFor('finitionsList');
                break;
            case 'body':
                $route = $routeparser->urlFor('bodiesList');
                break;
            case 'transmission':
                $route = $routeparser->urlFor('transmissionsList');
                break;
            case 'brand':
                $route = $routeparser->urlFor('brandsList');
                break;
            default:
                throw new \RuntimeException('Unknown property ' . $property);
        }
        return $route;
    }

    /**
     * Get object name from route property
     *
     * @param string $property Route property
     *
     * @return string
     */
    public static function getClassForPropName(string $property): string
    {
        $classname = '\GaletteAuto\\';
        switch ($property) {
            case 'brand':
                $classname .= 'Brand';
                break;
            case 'color':
                $classname .= 'Color';
                break;
            case 'state':
                $classname .= 'State';
                break;
            case 'finition':
                $classname .= 'Finition';
                break;
            case 'body':
                $classname .= 'Body';
                break;
            case 'transmission':
                $classname .= 'Transmission';
                break;
            default:
                throw new \RuntimeException('Unknown property ' . $property);
        }
        return $classname;
    }

    /**
     * Builds the SELECT statement
     *
     * @return Select SELECT statement
     */
    private function buildSelect(): Select
    {
        try {
            $select = $this->zdb->select($this->table);
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
     *
     * @return void
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
                    //@phpstan-ignore-next-line
                    static::PK => new Expression('COUNT(' . static::PK . ')')
                ]
            );

            $results = $this->zdb->execute($countSelect);
            $result = $results->current();

            //@phpstan-ignore-next-line
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
     *
     * @return integer
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Display localized count for object
     *
     * @return string
     */
    public function displayCount(): string
    {
        return str_replace(
            '%count',
            (string)$this->getCount(),
            $this->getLocalizedCount()
        );
    }

    /**
     * Get localized count string for object list
     *
     * @return string
     */
    abstract protected function getLocalizedCount(): string;
}
