<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto;

use Galette\Core\Preferences;
use Galette\Core\PreferencesSchema;

/**
 * Plugin preferences
 *
 * They are stored by core preferences, which this class declares them to and
 * reads them from.
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
final class AutoPreferences
{
    public const string PREFIX = 'pref_auto_';
    /** Prefix of the yes/no preferences making a vehicle field required */
    public const string REQUIRED_PREFIX = self::PREFIX . 'required_';

    /**
     * Former local configuration file for required fields
     *
     * It only seeds the preferences when core creates them; it can be
     * removed afterwards.
     */
    public const string LEGACY_FILE = 'local_auto_required.inc.php';

    /**
     * Vehicle fields that are always required
     *
     * Database does not allow them to be empty.
     *
     * @var array<string>
     */
    public const array ALWAYS_REQUIRED = [
        'model',
        'first_registration_date',
        'first_circulation_date',
        'color',
        'state',
        'body',
        'transmission',
        'finition',
    ];

    /**
     * Vehicle fields that may be required, with their default
     *
     * @var array<string, bool>
     */
    public const array OPTIONAL_FIELDS = [
        'name' => true,
        'registration' => true,
        'fuel' => true,
        'mileage' => false,
        'seats' => false,
        'horsepower' => false,
        'engine_size' => false,
        'chassis_number' => false,
        'comment' => false,
    ];

    /**
     * Constructor
     *
     * @param Preferences $preferences Core preferences
     */
    public function __construct(private readonly Preferences $preferences)
    {
    }

    /**
     * Get the preferences the plugin declares
     *
     * Defaults come from the former local configuration file when there is
     * one, so its values are kept when core creates the preferences.
     *
     * @param string $config_path Configuration directory
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getSchema(string $config_path = GALETTE_CONFIG_PATH): array
    {
        $legacy = self::getLegacyRequired($config_path);

        $schema = [];
        foreach (self::OPTIONAL_FIELDS as $field => $default) {
            $schema[self::REQUIRED_PREFIX . $field] = [
                'type' => PreferencesSchema::TYPE_BOOL,
                'default' => $legacy !== null ? isset($legacy[$field]) : $default,
            ];
        }
        return $schema;
    }

    /**
     * Get required fields from the former local configuration file
     *
     * @param string $config_path Configuration directory
     *
     * @return ?array<string, mixed> Null when there is no such file
     */
    private static function getLegacyRequired(string $config_path): ?array
    {
        $file = $config_path . self::LEGACY_FILE;
        if (!file_exists($file)) {
            return null;
        }
        $required = require $file;
        return is_array($required) ? $required : null;
    }

    /**
     * Is a vehicle field required?
     *
     * @param string $field Field name
     */
    public function isRequired(string $field): bool
    {
        if (in_array($field, self::ALWAYS_REQUIRED, true)) {
            return true;
        }
        if (!isset(self::OPTIONAL_FIELDS[$field])) {
            return false;
        }
        //core hands a false boolean back as an empty string
        return (bool)$this->preferences->getPluginValue(self::REQUIRED_PREFIX . $field);
    }

    /**
     * Get required vehicle fields
     *
     * @return array<string, true> Field names as keys
     */
    public function getRequired(): array
    {
        $required = [];
        foreach (array_merge(self::ALWAYS_REQUIRED, array_keys(self::OPTIONAL_FIELDS)) as $field) {
            if ($this->isRequired($field)) {
                $required[$field] = true;
            }
        }
        return $required;
    }

    /**
     * Get the yes/no preferences, named after their field, for templates
     *
     * @return array<string, bool>
     */
    public function toArray(): array
    {
        $values = [];
        foreach (array_keys(self::OPTIONAL_FIELDS) as $field) {
            $values[$field] = $this->isRequired($field);
        }
        return $values;
    }
}
