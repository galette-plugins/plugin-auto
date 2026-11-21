<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto;

use ArrayObject;
use Galette\DynamicFields\DynamicField;

/**
 * Vehicle types (car, motorbike, moped, truck...) for galette Auto plugin
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class VehicleType extends AbstractObject
{
    public const string TABLE = 'types';
    public const string PK = 'id_type';
    public const string FIELD = 'type';
    public const string LIST_ROUTE = 'typesList';
    public const string FIELDS_TABLE = 'types_fields';

    public const int HIDDEN = 0;
    public const int OPTIONAL = 1;
    public const int REQUIRED = 2;

    /**
     * Vehicle fields each type configures; the type and the model are always required
     *
     * @var array<string>
     */
    public const array FIELDS = [
        'name',
        'registration',
        'first_registration_date',
        'first_circulation_date',
        'mileage',
        'seats',
        'color',
        'state',
        'body',
        'transmission',
        'finition',
        'chassis_number',
        'horsepower',
        'engine_size',
        'fuel',
        'comment',
    ];

    /**
     * Prefix of dynamic fields in fields modes, followed by the field ID
     */
    public const string DYNAMIC_PREFIX = 'dynfield_';

    /**
     * Fields modes, loaded on demand
     *
     * @var ?array<string, int>
     */
    private ?array $fields = null;

    /**
     * Loads a record
     *
     * @param int $id id of the record
     */
    public function load(int $id): bool
    {
        $this->fields = null;
        return parent::load($id);
    }

    /**
     * Populate from a resultset row, which may come from a join
     *
     * @param ArrayObject<string, mixed> $row Resultset row
     */
    public function loadFromRow(ArrayObject $row): self
    {
        $this->fields = null;
        parent::loadFromRow($row);
        return $this;
    }

    /**
     * Reset to an empty type, without fields modes
     */
    public function reset(): static
    {
        $this->fields = null;
        parent::reset();
        return $this;
    }

    /**
     * Store current record and its fields modes
     *
     * @param bool $new New record or existing one
     *
     * @throws \Throwable
     */
    public function store(bool $new = false): void
    {
        $transaction = !$this->zdb->inTransaction();
        try {
            if ($transaction) {
                $this->zdb->beginTransaction();
            }
            parent::store($new);
            if ($this->fields !== null) {
                $this->storeFields();
            }
            if ($transaction) {
                $this->zdb->commit();
            }
        } catch (\Throwable $e) {
            if ($transaction) {
                $this->zdb->rollback();
            }
            throw $e;
        }
    }

    /**
     * Replace stored fields modes
     */
    private function storeFields(): void
    {
        $delete = $this->zdb->delete(AUTO_PREFIX . self::FIELDS_TABLE);
        $delete->where([self::PK => $this->id]);
        $this->zdb->execute($delete);

        foreach ($this->getFields() as $field => $mode) {
            $insert = $this->zdb->insert(AUTO_PREFIX . self::FIELDS_TABLE);
            $insert->values([
                self::PK => $this->id,
                'field' => $field,
                'mode' => $mode,
            ]);
            $this->zdb->execute($insert);
        }
    }

    /**
     * Get every configurable field mode; a field the type does not configure is optional
     *
     * Dynamic fields of vehicles are configured as well, keyed by DYNAMIC_PREFIX
     * and their ID; one the type does not configure follows its own setting.
     *
     * @return array<string, int>
     */
    public function getFields(): array
    {
        if ($this->fields === null) {
            $this->fields = array_fill_keys(self::FIELDS, self::OPTIONAL);
            foreach ($this->getDynamicFields() as $key => $field) {
                $this->fields[$key] = (int)$field['field_required'] === 1 ? self::REQUIRED : self::OPTIONAL;
            }
            if ($this->id !== null) {
                $select = $this->zdb->select(AUTO_PREFIX . self::FIELDS_TABLE);
                $select->where([self::PK => $this->id]);
                foreach ($this->zdb->execute($select) as $row) {
                    if (isset($this->fields[$row['field']])) {
                        $this->fields[$row['field']] = (int)$row['mode'];
                    }
                }
            }
        }
        return $this->fields;
    }

    /**
     * Get the dynamic fields of vehicles a type configures, separators left out
     *
     * @return array<string, ArrayObject<string, mixed>> Fields rows, keyed by DYNAMIC_PREFIX and their ID
     */
    private function getDynamicFields(): array
    {
        $select = $this->zdb->select(DynamicField::TABLE);
        $select
            ->where(['field_form' => Auto::DYNAMIC_FORM])
            ->where->notEqualTo('field_type', DynamicField::SEPARATOR);
        $select->order(['field_index', DynamicField::PK]);

        $fields = [];
        foreach ($this->zdb->execute($select) as $row) {
            /** @var ArrayObject<string, mixed> $row */
            $fields[self::DYNAMIC_PREFIX . $row[DynamicField::PK]] = $row;
        }
        return $fields;
    }

    /**
     * Get the names of the dynamic fields of vehicles a type configures
     *
     * @return array<string, string> Names, keyed by DYNAMIC_PREFIX and the field ID
     */
    public function getDynamicFieldsNames(): array
    {
        return array_map(
            fn(ArrayObject $field): string => (string)$field['field_name'],
            $this->getDynamicFields()
        );
    }

    /**
     * Get a field mode
     *
     * @param string $field Field name
     */
    public function getFieldMode(string $field): int
    {
        if ($field === 'type' || $field === 'model') {
            return self::REQUIRED;
        }
        return $this->getFields()[$field] ?? self::OPTIONAL;
    }

    /**
     * Is a field required?
     *
     * @param string $field Field name
     */
    public function isRequired(string $field): bool
    {
        return $this->getFieldMode($field) === self::REQUIRED;
    }

    /**
     * Is a field hidden?
     *
     * @param string $field Field name
     */
    public function isHidden(string $field): bool
    {
        return $this->getFieldMode($field) === self::HIDDEN;
    }

    /**
     * Get required fields, the type and the model included
     *
     * @return array<string, true>
     */
    public function getRequired(): array
    {
        $required = ['type' => true, 'model' => true];
        foreach ($this->getFields() as $field => $mode) {
            if ($mode === self::REQUIRED) {
                $required[$field] = true;
            }
        }
        return $required;
    }

    /**
     * Set fields modes; unknown fields and modes are ignored
     *
     * @param array<string, mixed> $modes Modes, by field name
     */
    public function setFields(array $modes): self
    {
        $fields = $this->getFields();
        foreach ($modes as $field => $mode) {
            if (
                isset($fields[$field])
                && is_numeric($mode)
                && in_array((int)$mode, [self::HIDDEN, self::OPTIONAL, self::REQUIRED], strict: true)
            ) {
                $fields[$field] = (int)$mode;
            }
        }
        $this->fields = $fields;
        return $this;
    }

    /**
     * Get localized fields modes
     *
     * @return array<int, string>
     */
    public static function getModes(): array
    {
        return [
            self::HIDDEN => _T('Hidden', 'auto'),
            self::OPTIONAL => _T('Optional', 'auto'),
            self::REQUIRED => _T('Required', 'auto'),
        ];
    }

    /**
     * Get sequence name on PostgreSQL: the table uses an identity column
     */
    protected function getSequenceName(): string
    {
        return PREFIX_DB . AUTO_PREFIX . static::TABLE . '_' . static::PK . '_seq';
    }

    /**
     * Get field label
     */
    public function getFieldLabel(): string
    {
        return _T('Vehicle type', 'auto');
    }

    /**
     * Get list page title
     */
    public function getListTitle(): string
    {
        return _T("Vehicle types list", "auto");
    }

    /**
     * Get add button text
     */
    public function getAddText(): string
    {
        return _T("Add new vehicle type", "auto");
    }

    /**
     * Get localized count
     *
     * @param int $count Count
     */
    public function getCountLabel(int $count): string
    {
        return str_replace(
            '%count',
            (string)$count,
            _Tn(singular: '%count vehicle type', plural: '%count vehicle types', count: $count, domain: 'auto')
        );
    }

    /**
     * Get removal success message
     *
     * @param int $count Removed records count
     */
    public function getRemovedMessage(int $count): string
    {
        return sprintf(
            _Tn(
                singular: '%1$s vehicle type has been successfully deleted.',
                plural: '%1$s vehicle types have been successfully deleted.',
                count: $count,
                domain: 'auto'
            ),
            $count
        );
    }

    /**
     * Get message when removal is refused because the record is in use
     */
    public function getInUseMessage(): string
    {
        return _T('This vehicle type is used by one or more vehicles, it cannot be deleted.', 'auto');
    }

    /**
     * Get removal error message
     */
    public function getRemoveErrorMessage(): string
    {
        return _T('An error occurred trying to remove vehicle type :/', 'auto');
    }
}
