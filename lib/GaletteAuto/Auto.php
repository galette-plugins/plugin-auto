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

use ArrayObject;
use Analog\Analog;
use Galette\Core\Db;
use Galette\Core\Login;
use Galette\Core\Plugins;
use Galette\Entity\Adherent;
use Laminas\Db\Sql\Expression;

/**
 * Automobile Transmissions class for galette Auto plugin
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 *
 * @property integer $id
 * @property string $registration
 * @property string $name
 * @property string $first_registration_date
 * @property string $first_circulation_date
 * @property integer $mileage
 * @property string $comment
 * @property string $chassis_number
 * @property integer $seats
 * @property integer $horsepower
 * @property integer $engine_size
 * @property string $creation_date
 * @property integer $fuel
 * @property Color $color
 * @property Body $body
 * @property State $state
 * @property Transmission $transmission
 * @property Finition $finition
 * @property Model $model
 * @property int $owner_id
 * @property Adherent $owner
 * @property Picture $picture
 * @property History $history
 */
class Auto
{
    public const TABLE = 'cars';
    public const PK = 'id_car';

    private Plugins $plugins;
    private Db $zdb;

    private array $fields = [
        'id_car'                        => 'integer',
        'car_name'                      => 'string',
        'car_registration'              => 'string',
        'car_first_registration_date'   => 'date',
        'car_first_circulation_date'    => 'date',
        'car_mileage'                   => 'integer',
        'car_comment'                   => 'string',
        'car_creation_date'             => 'date',
        'car_chassis_number'            => 'string',
        'car_seats'                     => 'integer',
        'car_horsepower'                => 'integer',
        'car_engine_size'               => 'integer',
        'car_fuel'                      => 'integer',
        Color::PK                       => 'integer',
        Body::PK                        => 'integer',
        State::PK                       => 'integer',
        Transmission::PK                => 'integer',
        Finition::PK                    => 'integer',
        Model::PK                       => 'integer',
        Adherent::PK                    => 'integer'
    ];

    private array $required = [
        'name'                      => 1,
        'model'                     => 1,
        'first_registration_date'   => 1,
        'first_circulation_date'    => 1,
        'color'                     => 1,
        'state'                     => 1,
        'registration'              => 1,
        'body'                      => 1,
        'transmission'              => 1,
        'finition'                  => 1,
        'fuel'                      => 1
    ];

    private int $id;
    private string $registration;
    private string $name;
    private string $first_registration_date;
    private string $first_circulation_date;
    private ?int $mileage;
    private ?string $comment;
    private ?string $chassis_number;
    private ?int $seats;
    private ?int $horsepower;
    private ?int $engine_size;
    private string $creation_date;
    private int $fuel;

    //External objects
    private Picture $picture;
    private Finition $finition;
    private Color $color;
    private Model $model;
    private Transmission $transmission;
    private Body $body;
    private History $history;
    private State $state;
    private int $owner_id;
    private Adherent $owner;

    public const FUEL_PETROL = 1;
    public const FUEL_DIESEL = 2;
    public const FUEL_GAS = 3;
    public const FUEL_ELECTRICITY = 4;
    public const FUEL_BIO = 5;
    public const FUEL_HYBRID = 6;

    /** @var array<string, string> */
    private array $propnames; //textual properties names

    //do we have to fire a history entry?
    private bool $fire_history = false;

    //internal properties (not updatable outside the object)
    private array $internals = [
        'id',
        'creation_date',
        'history',
        'picture',
        'propnames',
        'internals',
        'fields',
        'fire_history',
        'plugins',
        'zdb'
    ];
    private array $errors = [];

    /**
     * Default constructor
     *
     * @param Plugins      $plugins Plugins
     * @param Db           $zdb     Database instance
     * @param ?ArrayObject $args    A resultset row to load
     */
    public function __construct(Plugins $plugins, Db $zdb, ?ArrayObject $args = null)
    {
        $this->plugins = $plugins;
        $this->zdb = $zdb;

        $this->propnames = [
            'name'                      => mb_strtolower(_T("Name", "auto")),
            'model'                     => mb_strtolower(_T("Model", "auto")),
            'registration'              => mb_strtolower(_T("Registration", "auto")),
            'first_registration_date'   => mb_strtolower(_T("First registration date", "auto")),
            'first_circulation_date'    => mb_strtolower(_T("First circulation date", "auto")),
            'mileage'                   => mb_strtolower(_T("Mileage", "auto")),
            'seats'                     => mb_strtolower(_T("Seats", "auto")),
            'horsepower'                => mb_strtolower(_T("Horsepower", "auto")),
            'engine_size'               => mb_strtolower(_T("Engine size", "auto")),
            'color'                     => mb_strtolower(_T("Color", "auto")),
            'state'                     => mb_strtolower(_T("State", "auto")),
            'finition'                  => mb_strtolower(_T("Finition", "auto")),
            'transmission'              => mb_strtolower(_T("Transmission", "auto")),
            'body'                      => mb_strtolower(_T("Body", "auto")),
            'fuel'                      => mb_strtolower(_T("Fuel", "auto")),
        ];

        $this->model = new Model($this->zdb);
        $this->color = new Color($this->zdb);
        $this->state = new State($this->zdb);

        $this->owner = new Adherent($this->zdb);
        $this->owner->disableAllDeps()->enableDep('parent');
        $this->transmission = new Transmission($this->zdb);
        $this->finition = new Finition($this->zdb);
        $this->picture = new Picture($this->plugins);
        $this->body = new Body($this->zdb);
        $this->history = new History($this->zdb);
        if ($args instanceof ArrayObject) {
            $this->loadFromRS($args);
        }
    }

    /**
     * Loads a car from its id
     *
     * @param integer $id the identifiant for the car to load
     *
     * @return boolean
     */
    public function load(int $id): bool
    {
        try {
            $select = $this->zdb->select(AUTO_PREFIX . self::TABLE);
            $select->where(
                [
                    self::PK => $id
                ]
            );

            $results = $this->zdb->execute($select);
            $result = $results->current();
            if (!$result instanceof ArrayObject) {
                throw new \RuntimeException('Vehicle not found');
            }
            $this->loadFromRS($result);
            return true;
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] Cannot load car from id `' . $id
                . '` | ' . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Populate object from a resultset row
     *
     * @param ArrayObject $r a resultset row
     *
     * @return void
     */
    private function loadFromRS(ArrayObject $r): void
    {
        $pk = self::PK;
        $this->id = (int)$r->$pk;
        $this->registration = $r->car_registration;
        $this->name = $r->car_name;
        $this->first_registration_date = $r->car_first_registration_date;
        $this->first_circulation_date = $r->car_first_circulation_date;
        $this->mileage = $r->car_mileage != null ? (int)$r->car_mileage : null;
        $this->comment = $r->car_comment;
        $this->chassis_number = $r->car_chassis_number;
        $this->seats = $r->car_seats != null ? (int)$r->car_seats : null;
        $this->horsepower = $r->car_horsepower != null ? (int)$r->car_horsepower : null;
        $this->engine_size = $r->car_engine_size != null ? (int)$r->car_engine_size : null;
        $this->creation_date = $r->car_creation_date;
        $this->fuel = $r->car_fuel != null ? (int)$r->car_fuel : null;
        //External objects
        $this->picture = new Picture($this->plugins, $this->id);
        $fpk = Finition::PK;
        $this->finition->load((int)$r->$fpk);
        $cpk = Color::PK;
        $this->color->load((int)$r->$cpk);
        $mpk = Model::PK;
        $this->model->load((int)$r->$mpk);
        $tpk = Transmission::PK;
        $this->transmission->load((int)$r->$tpk);
        $bpk = Body::PK;
        $this->body->load((int)$r->$bpk);
        $opk = Adherent::PK;
        $this->owner_id = (int)$r->$opk;
        $this->owner->load($this->owner_id);
        $spk = State::PK;
        $this->state->load((int)$r->$spk);
        $this->history->load($this->id);
    }

    /**
     * Return the list of available fuels
     *
     * @return array
     */
    public function listFuels(): array
    {
        //TODO: make this list configurable?
        $f = [
            self::FUEL_PETROL       => _T("Petrol", "auto"),
            self::FUEL_DIESEL       => _T("Diesel", "auto"),
            self::FUEL_GAS          => _T("Gas", "auto"),
            self::FUEL_HYBRID       => _T("Hybrid", "auto"),
            self::FUEL_ELECTRICITY  => _T("Electricity", "auto"),
            self::FUEL_BIO          => _T("Bio", "auto")
        ];
        return $f;
    }

    /**
     * Stores the vehicle in the database
     *
     * @param boolean $new true if it's a new record, false to update on
     *                     that already exists. Defaults to false
     *
     * @return boolean
     */
    public function store(bool $new = false): bool
    {
        global $hist;

        if ($new) {
            $this->creation_date = date('Y-m-d');
        }

        try {
            $values = [];

            foreach ($this->fields as $k => $v) {
                switch ($k) {
                    case self::PK:
                        break;
                    case Color::PK:
                        $values[$k] = $this->color->id;
                        break;
                    case Body::PK:
                        $values[$k] = $this->body->id;
                        break;
                    case State::PK:
                        $values[$k] = $this->state->id;
                        break;
                    case Transmission::PK:
                        $values[$k] = $this->transmission->id;
                        break;
                    case Finition::PK:
                        $values[$k] = $this->finition->id;
                        break;
                    case Model::PK:
                        $values[$k] = $this->model->id;
                        break;
                    case Adherent::PK:
                        $values[$k] = $this->owner->id;
                        break;
                    default:
                        $propName = substr($k, 4, strlen($k));
                        switch ($v) {
                            case 'string':
                            case 'date':
                                $values[$k] = $this->$propName ?? null;
                                break;
                            case 'integer':
                                $values[$k] = (
                                    (!empty($this->$propName))
                                        ? $this->$propName
                                        : new Expression('NULL')
                                );
                                break;
                            default:
                                $values[$k] = $this->$propName;
                                break;
                        }
                        break;
                }
            }

            if ($new === true) {
                $insert = $this->zdb->insert(AUTO_PREFIX . self::TABLE);
                $insert->values($values);
                $add = $this->zdb->execute($insert);

                if ($add->count() > 0) {
                    /** @phpstan-ignore-next-line */
                    $this->id = (int)$this->zdb->driver->getLastGeneratedValue(
                        $this->zdb->isPostgres()
                            ? PREFIX_DB . AUTO_PREFIX . self::TABLE . '_id_seq'
                            : null
                    );

                    // logging
                    $hist->add(
                        _T("New car added", "auto"),
                        strtoupper($this->name)
                    );
                    $this->history->load((int)$this->id);

                    //handle picture for newly added cars
                    $this->picture = new Picture($this->plugins, (int)$this->id);
                    $this->handlePicture();
                } else {
                    $hist->add(_T("Fail to add new car.", "auto"));
                    throw new \Exception(
                        'An error occurred inserting new car!'
                    );
                }
            } else {
                $update = $this->zdb->update(AUTO_PREFIX . self::TABLE);
                $update->set($values)->where(
                    [
                        self::PK => $this->id
                    ]
                );
                $edit = $this->zdb->execute($update);
                //edit == 0 does not mean there were an error, but that there
                //were nothing to change
                if ($edit->count() > 0) {
                    $hist->add(
                        _T("Car updated", "auto"),
                        strtoupper($this->name)
                    );
                }
            }

            //if all goes well, we check to add an entry into car's history
            $h = $this->history->getLatest();
            if (!$new && $h !== false) {
                foreach ($h as $k => $v) {
                    if ($k != 'history_date' && $this->$k != $v) {
                        //if one has been modified, we flag to add an entry event
                        $this->fire_history = true;
                        break;
                    }
                }
            } elseif ($new) {
                //no history entry... yet! Let's create one.
                $this->fire_history = true;
            }

            if ($this->fire_history) {
                $h_props = [];
                foreach ($this->history->fields as $prop) {
                    if ($prop != 'history_date') {
                        $h_props[$prop] = $this->$prop;
                    } else {
                        $h_props[$prop] = date('Y-m-d H:i:s');
                    }
                }
                $this->history->register($h_props);
                $this->fire_history = false;
            }

            return true;
        } catch (\Exception $e) {
            Analog::log(
                '[' . get_class($this) . '] An error has occurred '
                . (($new) ? 'inserting' : 'updating') . ' car | '
                . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * List object's properties
     *
     * @param boolean $restrict true to exclude $this->internals from returned
     *                          result, false otherwise. Default to false
     *
     * @return array
     */
    private function getAllProperties(bool $restrict = false): array
    {
        $result = [];
        foreach (get_class_vars(static::class) as $key => $value) {
            if (
                !$restrict
                || !in_array($key, $this->internals)
            ) {
                $result[] = $key;
            }
        }
        return $result;
    }

    /**
     * Get object's properties. List only properties that can be modified
     *   externally (ie. not in $this->internals)
     *
     * @return array
     */
    public function getProperties(): array
    {
        $properties = $this->getAllProperties(true);
        $to_unset = ['required', 'errors'];
        foreach ($to_unset as $prop) {
            unset($properties[array_search($prop, $properties)]);
        }
        return $properties;
    }

    /**
     * Does the current car has a picture?
     *
     * @return boolean
     */
    public function hasPicture(): bool
    {
        return $this->picture->hasPicture();
    }

    /**
     * Set car's owner to current logged user
     *
     * @param Login $login Login instance
     *
     * @return void
     */
    public function appropriateCar(Login $login): void
    {
        $this->owner_id = $login->id;
        $this->owner->load($this->owner_id);
    }

    /**
     * Returns plain text property name, generally used for translations
     *
     * @param string $name property name
     *
     * @return string property
     */
    public function getPropName(string $name): string
    {
        if (isset($this->propnames[$name])) {
            return $this->propnames[$name];
        } else {
            throw new \UnexpectedValueException('Unknown propname ' . $name);
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
        $forbidden = [];
        if (!in_array($name, $forbidden)) {
            switch ($name) {
                case self::PK:
                    return $this->id;
                case Adherent::PK:
                    return $this->owner->id;
                case Color::PK:
                    return $this->color->id;
                case State::PK:
                    return $this->state->id;
                case 'car_registration':
                    return $this->registration;
                case 'first_registration_date':
                case 'first_circulation_date':
                case 'creation_date':
                    if (isset($this->$name)) {
                        try {
                            $d = new \DateTime($this->$name);
                            return $d->format(_T("Y-m-d"));
                        } catch (\Exception $e) {
                            //oops, we've got a bad date :/
                            Analog::log(
                                'Bad date (' . $this->$name . ') | '
                                . $e->getMessage(),
                                Analog::WARNING
                            );
                            return $this->$name;
                        }
                    }
                    return null;
                case 'picture':
                    return $this->picture;
                default:
                    return $this->$name ?? '';
            }
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
     * Global setter method
     *
     * @param string $name  name of the property we want to assign a value to
     * @param mixed  $value a relevant value for the property
     *
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        if (!in_array($name, $this->internals)) {
            switch ($name) {
                case 'finition':
                    $this->finition->load((int)$value);
                    break;
                case 'color':
                    $this->color->load((int)$value);
                    break;
                case 'model':
                    $this->model->load((int)$value);
                    break;
                case 'transmission':
                    $this->transmission->load((int)$value);
                    break;
                case 'body':
                    $this->body->load((int)$value);
                    break;
                case 'owner_id':
                    $this->owner_id = (int)$value;
                    $this->owner->load($this->owner_id);
                    break;
                case 'state':
                    $this->state->load((int)$value);
                    break;
                default:
                    $this->$name = $value;
                    break;
            }
        } else {
            Analog::log(
                '[' . get_class($this) . '] Trying to set an internal property (`'
                . $name . '`)',
                Analog::INFO
            );
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
        $knowns = [
            self::PK,
            Adherent::PK,
            Color::PK,
            State::PK
        ];
        if (in_array($name, $knowns)) {
            return true;
        }

        return property_exists($this, $name);
    }

    /**
     * Check posted values validity
     *
     * @param array $post All values to check, basically the $_POST array
     *                    after sending the form
     *
     * @return boolean
     */
    public function check(array $post): bool
    {
        $this->errors = [];

        //check for required fields, and correct values
        $required = $this->getRequired();
        foreach ($this->getProperties() as $prop) {
            $value = $post[$prop] ?? null;

            if (($value == '' || $value == null) && in_array($prop, array_keys($required))) {
                $this->errors[] = str_replace(
                    '%field',
                    '<a href="#' . $prop . '">' . $this->getPropName($prop) . '</a>',
                    _T("- Mandatory field %field empty.")
                );
                continue;
            }

            switch ($prop) {
                //string values with special check
                case 'registration':
                    if (mb_strlen($value) <= 10) {
                        $this->$prop = $value;
                    } else {
                        $this->errors[] = str_replace(
                            [
                                '%maxsize',
                                '%field',
                                '%cursize'
                            ],
                            [
                                '10',
                                $this->getPropName($prop),
                                (string)mb_strlen($value)
                            ],
                            _T("- Maximum size for %field is %maxsize (current %cursize)!", "auto")
                        );
                    }
                    break;
                    //string values, no check
                case 'name':
                case 'comment':
                case 'chassis_number':
                    $this->$prop = $value;
                    break;
                    //dates
                case 'first_registration_date':
                case 'first_circulation_date':
                    try {
                        $d = \DateTime::createFromFormat(__("Y-m-d"), $value);
                        if ($d === false) {
                            //try with non localized date
                            $d = \DateTime::createFromFormat("Y-m-d", $value);
                            if ($d === false) {
                                throw new \Exception('Incorrect format');
                            }
                        }
                        $this->$prop = $d->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $this->errors[] = sprintf(
                            //TRANS: %1$s is the date format, %2$s is the field name
                            _T('- Wrong date format (%1$s) for %2$s!'),
                            __("Y-m-d"),
                            $this->getPropName($prop)
                        );
                    }
                    break;
                    //numeric values
                case 'mileage':
                case 'seats':
                case 'horsepower':
                case 'engine_size':
                    if (is_numeric(str_replace(' ', '', $value ?? ''))) {
                        $this->$prop = (int)$value;
                    } elseif ($value != '') {
                        $this->errors[] = str_replace(
                            '%s',
                            '<a href="#' . $prop . '">' . $this->getPropName($prop) . '</a>',
                            _T("- You must enter a positive integer for %s", "auto")
                        );
                    }
                    break;
                    //constants
                case 'fuel':
                    if (in_array($value, array_keys($this->listFuels()))) {
                        $this->fuel = (int)$value;
                    } else {
                        $this->errors[] = _T("- You must choose a fuel in the list", "auto");
                    }
                    break;
                    //external objects
                case 'finition':
                case 'color':
                case 'model':
                case 'transmission':
                case 'body':
                case 'state':
                    if ($value > 0) {
                        $this->$prop->load((int)$value);
                    } else {
                        $class = 'GaletteAuto\\' . ucwords($prop);
                        $name = $class::FIELD;
                        $this->errors[] = str_replace(
                            '%s',
                            '<a href="#' . $prop . '">' . $this->getPropName($name) . '</a>',
                            _T("- You must choose a %s in the list", "auto")
                        );
                    }
                    break;
                case 'owner':
                    //owner is not a property that can be set.
                    break;
                case 'owner_id':
                    if (isset($post['change_owner']) || !isset($this->id)) {
                        $value = (int)$value;
                        if ($value > 0) {
                            $this->owner_id = $value;
                            $this->owner->load($value);
                        } else {
                            $this->errors[] = _T("- you must attach an owner to this car", "auto");
                        }
                    }
                    break;
                default:
                    /** TODO: what's the default? */
                    Analog::log(
                        'Trying to edit an Auto property that is not handled in the source code! (prop is: '
                        . $prop . ')',
                        Analog::ERROR
                    );
                    break;
            }//switch
        }//foreach

        if (isset($this->id)) {
            //handle picture for updated cars
            $this->handlePicture();
        }

        //delete photo
        if (isset($post['del_photo'])) {
            if (!$this->picture->delete()) {
                $this->errors[]
                    = _T("An error occurred while trying to delete car's photo", "auto");
            }
        }

        return count($this->errors) === 0;
    }

    /**
     * Get errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get required fields
     *
     * @return array
     */
    public function getRequired(): array
    {
        $required = $this->required;

        if (file_exists(GALETTE_CONFIG_PATH . 'local_auto_required.inc.php')) {
            $required = require GALETTE_CONFIG_PATH . 'local_auto_required.inc.php';
        }

        return $required;
    }

    /**
     * Handle car picture upload
     *
     * @return void
     */
    private function handlePicture(): void
    {
        // picture upload
        if (isset($_FILES['photo'])) {
            if ($_FILES['photo']['tmp_name'] != '') {
                if (is_uploaded_file($_FILES['photo']['tmp_name'])) {
                    $res = $this->picture->store($_FILES['photo']);
                    if ($res < 0) {
                        $this->errors[] = $this->picture->getErrorMessage($res);
                    }
                }
            }
        }
    }
}
