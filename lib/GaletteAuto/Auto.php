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
use Galette\Core\Db;
use Galette\Core\Login;
use Galette\Core\Plugins;
use Galette\Entity\Adherent;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Vehicle entity for galette Auto plugin
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Auto
{
    public const string TABLE = 'cars';
    public const string PK = 'id_car';

    public const int FUEL_PETROL = 1;
    public const int FUEL_DIESEL = 2;
    public const int FUEL_GAS = 3;
    public const int FUEL_ELECTRICITY = 4;
    public const int FUEL_BIO = 5;
    public const int FUEL_HYBRID = 6;

    /**
     * Fields that can be posted, in the order they are checked
     *
     * @var array<string>
     */
    private const array POSTED_FIELDS = [
        'registration',
        'name',
        'first_registration_date',
        'first_circulation_date',
        'mileage',
        'comment',
        'chassis_number',
        'seats',
        'horsepower',
        'engine_size',
        'fuel',
        'finition',
        'color',
        'model',
        'transmission',
        'body',
        'state',
        'owner_id'
    ];

    private Plugins $plugins;
    private Db $zdb;

    /** @var array<string, int> */
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

    private ?int $id = null;
    private ?string $registration = null;
    private ?string $name = null;
    private ?string $first_registration_date = null;
    private ?string $first_circulation_date = null;
    private ?int $mileage = null;
    private ?string $comment = null;
    private ?string $chassis_number = null;
    private ?int $seats = null;
    private ?int $horsepower = null;
    private ?int $engine_size = null;
    private ?string $creation_date = null;
    private ?int $fuel = null;

    //External objects
    private ?Picture $picture = null;
    private Finition $finition;
    private Color $color;
    private Model $model;
    private Transmission $transmission;
    private Body $body;
    private ?History $history = null;
    private State $state;
    private ?int $owner_id = null;
    private ?Adherent $owner = null;

    /** @var array<string, string> */
    private array $propnames; //textual properties names

    /** @var array<int, string> */
    private array $errors = [];

    /**
     * Default constructor
     *
     * @param Plugins                     $plugins Plugins
     * @param Db                          $zdb     Database instance
     * @param ?ArrayObject<string, mixed> $args    A resultset row to load
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
        $this->transmission = new Transmission($this->zdb);
        $this->finition = new Finition($this->zdb);
        $this->body = new Body($this->zdb);
        if ($args instanceof ArrayObject) {
            $this->loadFromRS($args);
        }
    }

    /**
     * Loads a car from its id
     *
     * @param int $id the identifiant for the car to load
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
                '[' . static::class . '] Cannot load vehicle #' . $id . ' | ' . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Populate object from a resultset row
     *
     * @param ArrayObject<string, mixed> $r a resultset row
     */
    private function loadFromRS(ArrayObject $r): void
    {
        $this->id = (int)$r[self::PK];
        $this->registration = (string)$r['car_registration'];
        $this->name = (string)$r['car_name'];
        $this->first_registration_date = (string)$r['car_first_registration_date'];
        $this->first_circulation_date = (string)$r['car_first_circulation_date'];
        $this->mileage = $r['car_mileage'] !== null ? (int)$r['car_mileage'] : null;
        $this->comment = $r['car_comment'] !== null ? (string)$r['car_comment'] : null;
        $this->chassis_number = $r['car_chassis_number'] !== null ? (string)$r['car_chassis_number'] : null;
        $this->seats = $r['car_seats'] !== null ? (int)$r['car_seats'] : null;
        $this->horsepower = $r['car_horsepower'] !== null ? (int)$r['car_horsepower'] : null;
        $this->engine_size = $r['car_engine_size'] !== null ? (int)$r['car_engine_size'] : null;
        $this->creation_date = (string)$r['car_creation_date'];
        $this->fuel = $r['car_fuel'] !== null ? (int)$r['car_fuel'] : null;
        //External objects, from the row when they have been joined
        foreach (['finition', 'color', 'transmission', 'body', 'state'] as $property) {
            $class = $this->$property::class;
            if (isset($r[$class::FIELD])) {
                $this->$property->loadFromRow($r);
            } else {
                $this->$property->load((int)$r[$class::PK]);
            }
        }
        $this->model = isset($r[Model::FIELD])
            ? new Model($this->zdb, $r)
            : new Model($this->zdb, (int)$r[Model::PK]);
        $this->setOwner((int)$r[Adherent::PK]);
    }

    /**
     * Return the list of available fuels
     *
     * @return array<int, string> List of fuels
     */
    public function listFuels(): array
    {
        //TODO: make this list configurable?
        return [
            self::FUEL_PETROL       => _T("Petrol", "auto"),
            self::FUEL_DIESEL       => _T("Diesel", "auto"),
            self::FUEL_GAS          => _T("Gas", "auto"),
            self::FUEL_HYBRID       => _T("Hybrid", "auto"),
            self::FUEL_ELECTRICITY  => _T("Electricity", "auto"),
            self::FUEL_BIO          => _T("Bio", "auto")
        ];
    }

    /**
     * Get values to store in database
     *
     * @return array<string, mixed>
     */
    public function getStorableValues(): array
    {
        return [
            'car_name'                      => $this->name,
            'car_registration'              => $this->registration,
            'car_first_registration_date'   => $this->first_registration_date,
            'car_first_circulation_date'    => $this->first_circulation_date,
            'car_mileage'                   => $this->mileage,
            'car_comment'                   => $this->comment,
            'car_creation_date'             => $this->creation_date,
            'car_chassis_number'            => $this->chassis_number,
            'car_seats'                     => $this->seats,
            'car_horsepower'                => $this->horsepower,
            'car_engine_size'               => $this->engine_size,
            'car_fuel'                      => $this->fuel,
            Color::PK                       => $this->color->getId(),
            Body::PK                        => $this->body->getId(),
            State::PK                       => $this->state->getId(),
            Transmission::PK                => $this->transmission->getId(),
            Finition::PK                    => $this->finition->getId(),
            Model::PK                       => $this->model->getId(),
            Adherent::PK                    => $this->owner_id
        ];
    }

    /**
     * Get values tracked in history, as they are now
     *
     * @return array<string, mixed>
     */
    public function getHistoryValues(): array
    {
        return [
            self::PK            => $this->id,
            Adherent::PK        => $this->owner_id,
            'history_date'      => date('Y-m-d H:i:s'),
            'car_registration'  => $this->registration,
            Color::PK           => $this->color->getId(),
            State::PK           => $this->state->getId()
        ];
    }

    /**
     * Does the current car has a picture?
     */
    public function hasPicture(): bool
    {
        return $this->getPicture()->hasPicture();
    }

    /**
     * Set car's owner to current logged user
     *
     * @param Login $login Login instance
     */
    public function appropriateCar(Login $login): void
    {
        $this->setOwner((int)$login->id);
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
     * Check posted values validity
     *
     * @param array<string,mixed> $post   All values to check, basically the $_POST array
     *                                    after sending the form
     * @param VehicleAccess       $access Access rules for current user
     */
    public function check(array $post, VehicleAccess $access): bool
    {
        $this->errors = [];

        //check for required fields, and correct values
        $required = $this->getRequired();
        foreach (self::POSTED_FIELDS as $prop) {
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
                    if (mb_strlen((string)$value) <= 10) {
                        $this->registration = (string)$value;
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
                                (string)mb_strlen((string)$value)
                            ],
                            _T("- Maximum size for %field is %maxsize (current %cursize)!", "auto")
                        );
                    }
                    break;
                    //string values, no check
                case 'name':
                    $this->name = (string)$value;
                    break;
                case 'comment':
                    $this->comment = $value !== null && $value !== '' ? (string)$value : null;
                    break;
                case 'chassis_number':
                    $this->chassis_number = $value !== null && $value !== '' ? (string)$value : null;
                    break;
                    //dates
                case 'first_registration_date':
                case 'first_circulation_date':
                    $d = \DateTime::createFromFormat(__("Y-m-d"), (string)$value);
                    if ($d === false) {
                        //try with non localized date
                        $d = \DateTime::createFromFormat("Y-m-d", (string)$value);
                    }
                    if ($d === false) {
                        $this->errors[] = sprintf(
                            //TRANS: %1$s is the date format, %2$s is the field name
                            _T('- Wrong date format (%1$s) for %2$s!'),
                            __("Y-m-d"),
                            $this->getPropName($prop)
                        );
                    } elseif ($prop === 'first_registration_date') {
                        $this->first_registration_date = $d->format('Y-m-d');
                    } else {
                        $this->first_circulation_date = $d->format('Y-m-d');
                    }
                    break;
                    //numeric values
                case 'mileage':
                case 'seats':
                case 'horsepower':
                case 'engine_size':
                    $number = str_replace(' ', '', (string)$value);
                    if ($number === '') {
                        $this->$prop = null;
                    } elseif (is_numeric($number)) {
                        $this->$prop = (int)$number;
                    } else {
                        $this->errors[] = str_replace(
                            '%s',
                            '<a href="#' . $prop . '">' . $this->getPropName($prop) . '</a>',
                            _T("- You must enter a positive integer for %s", "auto")
                        );
                    }
                    break;
                    //constants
                case 'fuel':
                    if (in_array((int)$value, array_keys($this->listFuels()), true)) {
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
                    if ((int)$value <= 0 || !$this->$prop->load((int)$value)) {
                        $this->errors[] = str_replace(
                            '%s',
                            '<a href="#' . $prop . '">' . $this->getPropName($prop) . '</a>',
                            _T("- You must choose a %s in the list", "auto")
                        );
                    }
                    break;
                case 'owner_id':
                    if (isset($post['change_owner']) || $this->id === null) {
                        $value = (int)$value;
                        if (!$access->isManager()) {
                            //simple members only own their vehicles
                            $value = $access->getMemberId();
                        }
                        if ($value <= 0) {
                            $this->errors[] = _T("- you must attach an owner to this car", "auto");
                        } elseif (!$access->canManageMember($value)) {
                            Analog::log(
                                'Trying to attach vehicle to member #' . $value
                                . ' (user #' . $access->getMemberId() . ')',
                                Analog::WARNING
                            );
                            $this->errors[] = _T("- you cannot attach this car to this member", "auto");
                        } else {
                            $this->setOwner($value);
                        }
                    }
                    break;
            }//switch
        }//foreach

        //delete photo
        if (isset($post['del_photo'])) {
            if (!$this->getPicture()->delete()) {
                $this->errors[]
                    = _T("An error occurred while trying to delete car's photo", "auto");
            }
        }

        return count($this->errors) === 0;
    }

    /**
     * Get errors
     *
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get required fields
     *
     * @return array<string,int>
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
     * @param array<UploadedFileInterface> $files Files sent
     */
    public function handleFiles(array $files): bool
    {
        $this->errors = [];
        $this->picture = new Picture($this->plugins, (int)$this->id);
        if (!$this->picture->upload(request_files: $files, key: 'photo')) {
            $this->errors = array_merge($this->errors, $this->picture->uploadErrors());
        }

        return !count($this->errors);
    }

    /**
     * Get vehicle ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get vehicle name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get registration
     */
    public function getRegistration(): ?string
    {
        return $this->registration;
    }

    /**
     * Get first registration date, as Y-m-d
     */
    public function getFirstRegistrationDate(): ?string
    {
        return $this->first_registration_date;
    }

    /**
     * Get first circulation date, as Y-m-d
     */
    public function getFirstCirculationDate(): ?string
    {
        return $this->first_circulation_date;
    }

    /**
     * Get year of first circulation
     */
    public function getFirstCirculationYear(): ?int
    {
        if (empty($this->first_circulation_date)) {
            return null;
        }
        return (int)substr($this->first_circulation_date, 0, 4);
    }

    /**
     * Get creation date, as Y-m-d
     */
    public function getCreationDate(): ?string
    {
        return $this->creation_date;
    }

    /**
     * Get mileage
     */
    public function getMileage(): ?int
    {
        return $this->mileage;
    }

    /**
     * Get comment
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * Get chassis number
     */
    public function getChassisNumber(): ?string
    {
        return $this->chassis_number;
    }

    /**
     * Get number of seats
     */
    public function getSeats(): ?int
    {
        return $this->seats;
    }

    /**
     * Get horsepower
     */
    public function getHorsepower(): ?int
    {
        return $this->horsepower;
    }

    /**
     * Get engine size
     */
    public function getEngineSize(): ?int
    {
        return $this->engine_size;
    }

    /**
     * Get fuel, one of the FUEL_* constants
     */
    public function getFuel(): ?int
    {
        return $this->fuel;
    }

    /**
     * Get fuel label
     */
    public function getFuelLabel(): ?string
    {
        if ($this->fuel === null) {
            return null;
        }
        return $this->listFuels()[$this->fuel] ?? null;
    }

    /**
     * Get model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Get color
     */
    public function getColor(): Color
    {
        return $this->color;
    }

    /**
     * Get state
     */
    public function getState(): State
    {
        return $this->state;
    }

    /**
     * Get transmission
     */
    public function getTransmission(): Transmission
    {
        return $this->transmission;
    }

    /**
     * Get finition
     */
    public function getFinition(): Finition
    {
        return $this->finition;
    }

    /**
     * Get body
     */
    public function getBody(): Body
    {
        return $this->body;
    }

    /**
     * Get owner ID
     */
    public function getOwnerId(): ?int
    {
        return $this->owner_id;
    }

    /**
     * Get owner, loaded on first call
     */
    public function getOwner(): Adherent
    {
        if ($this->owner === null) {
            $this->owner = new Adherent($this->zdb);
            $this->owner->disableAllDeps();
            if ($this->owner_id !== null && $this->owner_id > 0) {
                $this->owner->load($this->owner_id);
            }
        }
        return $this->owner;
    }

    /**
     * Set owner
     *
     * @param int $id_adh Member ID
     */
    public function setOwner(int $id_adh): self
    {
        $this->owner_id = $id_adh;
        $this->owner = null;
        return $this;
    }

    /**
     * Set owner from an already loaded member
     *
     * @param Adherent $owner Owner
     */
    public function setOwnerMember(Adherent $owner): self
    {
        $this->owner_id = (int)$owner->id;
        $this->owner = $owner;
        return $this;
    }

    /**
     * Set ID, once stored
     *
     * @param int $id Vehicle ID
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        $this->history = null;
        $this->picture = null;
        return $this;
    }

    /**
     * Set creation date
     *
     * @param string $date Date, as Y-m-d
     */
    public function setCreationDate(string $date): self
    {
        $this->creation_date = $date;
        return $this;
    }

    /**
     * Get picture
     */
    public function getPicture(): Picture
    {
        if ($this->picture === null) {
            $this->picture = new Picture($this->plugins, $this->id);
        }
        return $this->picture;
    }

    /**
     * Get history
     */
    public function getHistory(): History
    {
        if ($this->history === null) {
            $this->history = new History($this->zdb, $this->id);
        }
        return $this->history;
    }
}
