<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto;

use Galette\Core\Plugins\FixturesContext;
use Galette\Core\Plugins\FixturesProviderInterface;
use Galette\Entity\Adherent;
use GaletteAuto\Repository\Vehicles;
use RuntimeException;

/**
 * Sample vehicles for fixture members, run by galette:seed-fixtures
 *
 * Reference values (bodies, colors, brands...) are looked up by name and
 * created if missing. Vehicles are found back by their registration; once
 * removed, reference values of fixtures nothing uses anymore are removed too,
 * even if they were there before.
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 * @phpstan-type Vehicle array{
 *     name: string,
 *     registration: string,
 *     brand: string,
 *     model: string,
 *     fuel: int,
 *     color: string,
 *     body: string,
 *     finition: string,
 *     state: string,
 *     transmission: string,
 *     years: int,
 *     mileage: int,
 *     seats: int,
 *     horsepower: int,
 *     member: int,
 *     comment?: string,
 *     previous_member?: int
 * }
 */
class Fixtures implements FixturesProviderInterface
{
    /** @var array<class-string<AbstractObject>, list<string>> Reference values */
    private const array VALUES = [
        Body::class => ['Berline', 'Break', 'Citadine', 'SUV', 'Monospace', 'Utilitaire', 'Cabriolet'],
        Color::class => ['Blanc', 'Noir', 'Gris métallisé', 'Bleu nuit', 'Rouge', 'Vert anglais', 'Jaune'],
        Finition::class => ['Standard', 'Confort', 'Sport', 'Luxe'],
        State::class => ['Neuf', 'Très bon état', 'Bon état', 'À restaurer'],
        Transmission::class => ['Manuelle', 'Automatique'],
    ];

    /** @var array<string, list<string>> Models, by brand */
    private const array MODELS = [
        'Renault' => ['Clio', 'Mégane', 'Zoé', 'Kangoo', '4L'],
        'Peugeot' => ['208', '308', '3008', 'Partner'],
        'Citroën' => ['C3', 'Berlingo', '2CV'],
        'Volkswagen' => ['Golf', 'Polo', 'Combi'],
        'Toyota' => ['Yaris', 'Prius'],
        'Tesla' => ['Model 3'],
    ];

    /** @var list<Vehicle> Vehicles; years since first registration, member by position */
    private const array VEHICLES = [
        ['name' => 'Titine', 'registration' => 'FX-001-AA', 'brand' => 'Renault', 'model' => 'Clio',
            'fuel' => Auto::FUEL_PETROL, 'color' => 'Rouge', 'body' => 'Citadine', 'finition' => 'Standard',
            'state' => 'Bon état', 'transmission' => 'Manuelle', 'years' => 9, 'mileage' => 142300, 'seats' => 5,
            'horsepower' => 5, 'member' => 0],
        ['name' => 'La familiale', 'registration' => 'FX-002-AB', 'brand' => 'Peugeot', 'model' => '3008',
            'fuel' => Auto::FUEL_DIESEL, 'color' => 'Gris métallisé', 'body' => 'SUV', 'finition' => 'Confort',
            'state' => 'Très bon état', 'transmission' => 'Automatique', 'years' => 4, 'mileage' => 61200,
            'seats' => 5, 'horsepower' => 7, 'member' => 1],
        ['name' => 'Deuche', 'registration' => 'FX-003-AC', 'brand' => 'Citroën', 'model' => '2CV',
            'fuel' => Auto::FUEL_PETROL, 'color' => 'Jaune', 'body' => 'Berline', 'finition' => 'Standard',
            'state' => 'À restaurer', 'transmission' => 'Manuelle', 'years' => 48, 'mileage' => 231000, 'seats' => 4,
            'horsepower' => 2, 'member' => 2, 'comment' => 'Capote à changer avant la sortie de printemps.',
            'previous_member' => 5],
        ['name' => 'Électron', 'registration' => 'FX-004-AD', 'brand' => 'Renault', 'model' => 'Zoé',
            'fuel' => Auto::FUEL_ELECTRICITY, 'color' => 'Blanc', 'body' => 'Citadine', 'finition' => 'Confort',
            'state' => 'Très bon état', 'transmission' => 'Automatique', 'years' => 3, 'mileage' => 38900,
            'seats' => 5, 'horsepower' => 2, 'member' => 3],
        ['name' => 'Le combi', 'registration' => 'FX-005-AE', 'brand' => 'Volkswagen', 'model' => 'Combi',
            'fuel' => Auto::FUEL_PETROL, 'color' => 'Vert anglais', 'body' => 'Monospace', 'finition' => 'Standard',
            'state' => 'Bon état', 'transmission' => 'Manuelle', 'years' => 39, 'mileage' => 312400, 'seats' => 7,
            'horsepower' => 9, 'member' => 4, 'comment' => 'Indispensable pour les sorties du club.'],
        ['name' => 'Golfette', 'registration' => 'FX-006-AF', 'brand' => 'Volkswagen', 'model' => 'Golf',
            'fuel' => Auto::FUEL_DIESEL, 'color' => 'Bleu nuit', 'body' => 'Berline', 'finition' => 'Sport',
            'state' => 'Bon état', 'transmission' => 'Manuelle', 'years' => 7, 'mileage' => 118700, 'seats' => 5,
            'horsepower' => 6, 'member' => 6],
        ['name' => 'Discrète', 'registration' => 'FX-007-AG', 'brand' => 'Toyota', 'model' => 'Prius',
            'fuel' => Auto::FUEL_HYBRID, 'color' => 'Gris métallisé', 'body' => 'Berline', 'finition' => 'Luxe',
            'state' => 'Très bon état', 'transmission' => 'Automatique', 'years' => 5, 'mileage' => 87400,
            'seats' => 5, 'horsepower' => 5, 'member' => 7],
        ['name' => 'Fourgonnette', 'registration' => 'FX-008-AH', 'brand' => 'Renault', 'model' => 'Kangoo',
            'fuel' => Auto::FUEL_DIESEL, 'color' => 'Blanc', 'body' => 'Utilitaire', 'finition' => 'Standard',
            'state' => 'Bon état', 'transmission' => 'Manuelle', 'years' => 11, 'mileage' => 198200, 'seats' => 2,
            'horsepower' => 6, 'member' => 8, 'comment' => 'Prêtée pour les déménagements de matériel.'],
        ['name' => 'Quatrelle', 'registration' => 'FX-009-AJ', 'brand' => 'Renault', 'model' => '4L',
            'fuel' => Auto::FUEL_PETROL, 'color' => 'Bleu nuit', 'body' => 'Citadine', 'finition' => 'Standard',
            'state' => 'Bon état', 'transmission' => 'Manuelle', 'years' => 41, 'mileage' => 154000, 'seats' => 4,
            'horsepower' => 4, 'member' => 10, 'previous_member' => 0],
        ['name' => 'Fusée', 'registration' => 'FX-010-AK', 'brand' => 'Tesla', 'model' => 'Model 3',
            'fuel' => Auto::FUEL_ELECTRICITY, 'color' => 'Noir', 'body' => 'Berline', 'finition' => 'Luxe',
            'state' => 'Neuf', 'transmission' => 'Automatique', 'years' => 1, 'mileage' => 8200, 'seats' => 5,
            'horsepower' => 4, 'member' => 11],
        ['name' => 'Petite citadine', 'registration' => 'FX-011-AL', 'brand' => 'Toyota', 'model' => 'Yaris',
            'fuel' => Auto::FUEL_HYBRID, 'color' => 'Rouge', 'body' => 'Citadine', 'finition' => 'Confort',
            'state' => 'Très bon état', 'transmission' => 'Automatique', 'years' => 2, 'mileage' => 21500,
            'seats' => 5, 'horsepower' => 4, 'member' => 12],
        ['name' => 'Le break', 'registration' => 'FX-012-AM', 'brand' => 'Peugeot', 'model' => '308',
            'fuel' => Auto::FUEL_DIESEL, 'color' => 'Noir', 'body' => 'Break', 'finition' => 'Confort',
            'state' => 'Bon état', 'transmission' => 'Manuelle', 'years' => 8, 'mileage' => 167300, 'seats' => 5,
            'horsepower' => 6, 'member' => 13],
        ['name' => 'Ludospace', 'registration' => 'FX-013-AN', 'brand' => 'Citroën', 'model' => 'Berlingo',
            'fuel' => Auto::FUEL_DIESEL, 'color' => 'Gris métallisé', 'body' => 'Monospace',
            'finition' => 'Standard', 'state' => 'Bon état', 'transmission' => 'Manuelle', 'years' => 6,
            'mileage' => 104800, 'seats' => 5, 'horsepower' => 6, 'member' => 15],
        ['name' => 'Polochon', 'registration' => 'FX-014-AP', 'brand' => 'Volkswagen', 'model' => 'Polo',
            'fuel' => Auto::FUEL_PETROL, 'color' => 'Blanc', 'body' => 'Citadine', 'finition' => 'Sport',
            'state' => 'Très bon état', 'transmission' => 'Manuelle', 'years' => 3, 'mileage' => 29600, 'seats' => 5,
            'horsepower' => 5, 'member' => 16],
        ['name' => 'Cabriole', 'registration' => 'FX-015-AQ', 'brand' => 'Peugeot', 'model' => '208',
            'fuel' => Auto::FUEL_PETROL, 'color' => 'Jaune', 'body' => 'Cabriolet', 'finition' => 'Sport',
            'state' => 'Bon état', 'transmission' => 'Manuelle', 'years' => 6, 'mileage' => 73100, 'seats' => 4,
            'horsepower' => 5, 'member' => 18],
    ];

    /** @var array<class-string<AbstractObject>, array<string, int>> Reference values identifiers */
    private array $values = [];
    /** @var array<string, int> Models identifiers, by "brand/model" */
    private array $models = [];

    /**
     * Create reference values and vehicles of fixture members
     *
     * @param FixturesContext $context Fixtures context
     */
    public function seedFixtures(FixturesContext $context): string
    {
        if ($context->members === []) {
            return 'No member to give vehicles to';
        }

        $zdb = $context->zdb;
        foreach (self::VALUES as $class => $values) {
            foreach ($values as $value) {
                $this->values[$class][$value] = $this->getValueId($context, $class, $value);
            }
        }
        foreach (self::MODELS as $brand => $models) {
            $brand_id = $this->getValueId($context, Brand::class, $brand);
            foreach ($models as $model) {
                $this->models[$brand . '/' . $model] = $this->getModelId($context, $brand_id, $model);
            }
        }

        $access = new VehicleAccess($zdb, $context->login, $context->preferences);
        $preferences = new AutoPreferences($context->preferences);
        $vehicles = new Vehicles($context->plugins, $zdb, $context->login, $context->history);
        $today = new \DateTimeImmutable('today');

        foreach (self::VEHICLES as $position => $data) {
            $registration = $today->modify(sprintf('-%d years', $data['years']))
                ->modify(sprintf('-%d days', ($position * 47) % 300));
            $vehicle = new Auto($context->plugins, $zdb);
            $post = [
                'name' => $data['name'],
                'registration' => $data['registration'],
                'first_registration_date' => $registration->format('Y-m-d'),
                'first_circulation_date' => $registration->format('Y-m-d'),
                'mileage' => (string)$data['mileage'],
                'seats' => (string)$data['seats'],
                'horsepower' => (string)$data['horsepower'],
                'fuel' => (string)$data['fuel'],
                'comment' => $data['comment'] ?? '',
                'model' => (string)$this->models[$data['brand'] . '/' . $data['model']],
                'color' => (string)$this->values[Color::class][$data['color']],
                'body' => (string)$this->values[Body::class][$data['body']],
                'finition' => (string)$this->values[Finition::class][$data['finition']],
                'state' => (string)$this->values[State::class][$data['state']],
                'transmission' => (string)$this->values[Transmission::class][$data['transmission']],
                'owner_id' => (string)$context->getMemberId($data['member']),
            ];
            if (!$vehicle->check($post, $access, $preferences)) {
                throw new RuntimeException(sprintf(
                    'Invalid fixture vehicle %s: %s',
                    $data['name'],
                    implode(', ', $vehicle->getErrors())
                ));
            }
            $vehicles->store($vehicle);
            $this->backdate($context, $vehicle, $data, $position);
        }

        return sprintf('Created %d vehicles', count(self::VEHICLES));
    }

    /**
     * Vehicles are added today: pretend they have been added a while ago,
     * some of them by a former owner
     *
     * @param FixturesContext $context  Fixtures context
     * @param Auto            $vehicle  Stored vehicle
     * @param Vehicle         $data     Vehicle data
     * @param int             $position Vehicle position
     */
    private function backdate(FixturesContext $context, Auto $vehicle, array $data, int $position): void
    {
        $zdb = $context->zdb;
        $added = (new \DateTimeImmutable('today'))->modify(sprintf('-%d days', 30 + ($position * 61) % 900));

        $update = $zdb->update(AUTO_PREFIX . Auto::TABLE)
            ->set(['car_creation_date' => $added->format('Y-m-d')])
            ->where([Auto::PK => $vehicle->getId()]);
        $zdb->execute($update);

        $update = $zdb->update(AUTO_PREFIX . History::TABLE)
            ->set(['history_date' => $added->format('Y-m-d H:i:s')])
            ->where([Auto::PK => $vehicle->getId()]);
        $zdb->execute($update);

        $previous = isset($data['previous_member']) ? $context->getMemberId($data['previous_member']) : null;
        if ($previous !== null && $previous !== $context->getMemberId($data['member'])) {
            $insert = $zdb->insert(AUTO_PREFIX . History::TABLE)->values([
                Auto::PK => $vehicle->getId(),
                Adherent::PK => $previous,
                'history_date' => $added->modify('-3 years')->format('Y-m-d H:i:s'),
                'car_registration' => $data['registration'],
                Color::PK => $this->values[Color::class][$data['color']],
                State::PK => $this->values[State::class]['Très bon état'],
            ]);
            $zdb->execute($insert);
        }
    }

    /**
     * Get reference value identifier, create it if missing
     *
     * @param FixturesContext              $context Fixtures context
     * @param class-string<AbstractObject> $class   Value class
     * @param string                       $value   Value
     */
    private function getValueId(FixturesContext $context, string $class, string $value): int
    {
        $select = $context->zdb->select(AUTO_PREFIX . $class::TABLE)
            ->columns([$class::PK])
            ->where([$class::FIELD => $value])
            ->limit(1);
        $row = $context->zdb->execute($select)->current();
        if ($row) {
            return (int)$row->{$class::PK};
        }

        $object = new $class($context->zdb);
        $object->setValue($value);
        $object->store(true);
        return (int)$object->getId();
    }

    /**
     * Get model identifier, create it if missing
     *
     * @param FixturesContext $context  Fixtures context
     * @param int             $brand_id Brand identifier
     * @param string          $name     Model name
     */
    private function getModelId(FixturesContext $context, int $brand_id, string $name): int
    {
        $select = $context->zdb->select(AUTO_PREFIX . Model::TABLE)
            ->columns([Model::PK])
            ->where([Model::FIELD => $name, Brand::PK => $brand_id])
            ->limit(1);
        $row = $context->zdb->execute($select)->current();
        if ($row) {
            return (int)$row->{Model::PK};
        }

        $model = new Model($context->zdb);
        if (!$model->check(['brand' => $brand_id, 'model' => $name])) {
            throw new RuntimeException(sprintf('Invalid fixture model %s', $name));
        }
        $model->store(true);
        return (int)$model->getId();
    }

    /**
     * Remove fixture vehicles, then reference values nothing uses anymore
     *
     * @param FixturesContext $context Fixtures context
     */
    public function cleanFixtures(FixturesContext $context): void
    {
        $zdb = $context->zdb;

        $select = $zdb->select(AUTO_PREFIX . Auto::TABLE)->columns([Auto::PK]);
        $select->where->in('car_registration', array_column(self::VEHICLES, 'registration'));
        $ids = [];
        foreach ($zdb->execute($select) as $row) {
            $ids[] = (int)$row->{Auto::PK};
        }
        if ($ids !== []) {
            (new Vehicles($context->plugins, $zdb, $context->login, $context->history))->remove($ids);
        }

        foreach (self::MODELS as $brand => $models) {
            $select = $zdb->select(AUTO_PREFIX . Brand::TABLE)->columns([Brand::PK])->where([Brand::FIELD => $brand]);
            foreach ($zdb->execute($select) as $row) {
                $this->removeUnused(
                    $context,
                    Model::TABLE,
                    Model::PK,
                    [Model::FIELD => $models, Brand::PK => (int)$row->{Brand::PK}],
                    [Auto::TABLE]
                );
            }
        }
        $this->removeUnused(
            $context,
            Brand::TABLE,
            Brand::PK,
            [Brand::FIELD => array_keys(self::MODELS)],
            [Model::TABLE]
        );

        foreach (self::VALUES as $class => $values) {
            //history keeps colors and states too
            $referencers = in_array($class, [Color::class, State::class], true)
                ? [Auto::TABLE, History::TABLE]
                : [Auto::TABLE];
            $this->removeUnused($context, $class::TABLE, $class::PK, [$class::FIELD => $values], $referencers);
        }
    }

    /**
     * Remove rows nothing references anymore
     *
     * @param FixturesContext                 $context     Fixtures context
     * @param string                          $table       Table name, without prefixes
     * @param string                          $pk          Primary key, also the referencing column
     * @param array<string, list<string>|int> $where       Rows to remove; lists are IN conditions
     * @param list<string>                    $referencers Referencing tables names, without prefixes
     */
    private function removeUnused(
        FixturesContext $context,
        string $table,
        string $pk,
        array $where,
        array $referencers
    ): void {
        $zdb = $context->zdb;
        $used = [];
        foreach ($referencers as $referencer) {
            $select = $zdb->select(AUTO_PREFIX . $referencer)->columns([$pk])->quantifier('DISTINCT');
            foreach ($zdb->execute($select) as $row) {
                $used[] = (int)$row->{$pk};
            }
        }

        $delete = $zdb->delete(AUTO_PREFIX . $table);
        foreach ($where as $column => $value) {
            if (is_array($value)) {
                $delete->where->in($column, $value);
            } else {
                $delete->where->equalTo($column, $value);
            }
        }
        if ($used !== []) {
            $delete->where->notIn($pk, $used);
        }
        $zdb->execute($delete);
    }
}
