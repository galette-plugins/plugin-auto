<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\tests\units;

use Galette\Entity\Adherent;
use Galette\Tests\GaletteTestCase;
use GaletteAuto\Auto;
use GaletteAuto\History;

/**
 * Core events listeners tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class PluginEventProvider extends GaletteTestCase
{
    protected int $seed = 20260926203512;
    protected bool $load_plugins = true;

    /** @var array<string, int> */
    private array $props = [];

    /**
     * Set up tests
     */
    public function setUp(): void
    {
        parent::setUp();

        $values = [
            'body' => 'Berline',
            'color' => 'Grey',
            'finition' => 'Standard',
            'state' => 'Correct',
            'transmission' => 'Manual',
            'brand' => 'Peugeot',
        ];
        foreach ($values as $property => $value) {
            $class = '\GaletteAuto\\' . ucfirst($property);
            $object = new $class($this->zdb);
            $object->setValue($value);
            $object->store(true);
            $this->props[$property] = $object->getId();
        }

        $model = new \GaletteAuto\Model($this->zdb);
        $this->assertTrue($model->check(['model' => '307', 'brand' => $this->props['brand']]));
        $model->store(true);
        $this->props['model'] = $model->getId();
    }

    /**
     * Create a vehicle and its history entry, bypassing repository
     *
     * @param int    $id_adh Owner ID
     * @param string $name   Vehicle name
     */
    private function createVehicle(int $id_adh, string $name): int
    {
        $insert = $this->zdb->insert(AUTO_PREFIX . Auto::TABLE);
        $insert->values([
            'car_name' => $name,
            'car_registration' => 'GA-123-TE',
            'car_first_registration_date' => '2001-02-12',
            'car_first_circulation_date' => '2001-02-13',
            'car_creation_date' => date('Y-m-d'),
            'car_fuel' => Auto::FUEL_DIESEL,
            \GaletteAuto\Color::PK => $this->props['color'],
            \GaletteAuto\Body::PK => $this->props['body'],
            \GaletteAuto\State::PK => $this->props['state'],
            \GaletteAuto\Transmission::PK => $this->props['transmission'],
            \GaletteAuto\Finition::PK => $this->props['finition'],
            \GaletteAuto\Model::PK => $this->props['model'],
            Adherent::PK => $id_adh,
        ]);
        $this->zdb->execute($insert);

        $select = $this->zdb->select(AUTO_PREFIX . Auto::TABLE);
        $select->columns([Auto::PK])->where(['car_name' => $name])->order(Auto::PK . ' DESC')->limit(1);
        $car_id = (int)$this->zdb->execute($select)->current()[Auto::PK];

        $this->addHistory($car_id, $id_adh, '2020-01-01 10:00:00');
        return $car_id;
    }

    /**
     * Add a vehicle history entry
     *
     * @param int    $car_id Vehicle ID
     * @param int    $id_adh Owner ID
     * @param string $date   Entry date
     */
    private function addHistory(int $car_id, int $id_adh, string $date): void
    {
        $insert = $this->zdb->insert(AUTO_PREFIX . History::TABLE);
        $insert->values([
            Auto::PK => $car_id,
            Adherent::PK => $id_adh,
            'history_date' => $date,
            'car_registration' => 'GA-123-TE',
            \GaletteAuto\Color::PK => $this->props['color'],
            \GaletteAuto\State::PK => $this->props['state'],
        ]);
        $this->zdb->execute($insert);
    }

    /**
     * Get owners of a vehicle history entries, oldest first
     *
     * @param int $car_id Vehicle ID
     *
     * @return array<int, int>
     */
    private function getHistoryOwners(int $car_id): array
    {
        $select = $this->zdb->select(AUTO_PREFIX . History::TABLE);
        $select->columns([Adherent::PK])->where([Auto::PK => $car_id])->order('history_date');
        return array_map(
            fn($row) => (int)$row[Adherent::PK],
            $this->zdb->execute($select)->toArray()
        );
    }

    /**
     * Removing a member removes their vehicles, and drops them from other vehicles history
     */
    public function testMemberRemoval(): void
    {
        $member = $this->getMemberOne();
        $other = $this->getMemberTwo();

        $own_car = $this->createVehicle($member->id, 'Own car');
        $sold_car = $this->createVehicle($member->id, 'Sold car');
        //sold car now belongs to the other member
        $update = $this->zdb->update(AUTO_PREFIX . Auto::TABLE);
        $update->set([Adherent::PK => $other->id])->where([Auto::PK => $sold_car]);
        $this->zdb->execute($update);
        $this->addHistory($sold_car, $other->id, '2021-01-01 10:00:00');
        $other_car = $this->createVehicle($other->id, 'Other car');

        $this->assertSame([$member->id, $other->id], $this->getHistoryOwners($sold_car));

        $this->logSuperAdmin();
        $members = new \Galette\Repository\Members();
        $this->assertTrue($members->removeMembers($member->id));

        $vehicles = $this->container->get(\GaletteAuto\Repository\Vehicles::class);
        $this->assertSame(
            [$sold_car => $other->id, $other_car => $other->id],
            $vehicles->getOwners([$own_car, $sold_car, $other_car])
        );
        $this->assertSame([], $this->getHistoryOwners($own_car));
        $this->assertSame([$other->id], $this->getHistoryOwners($sold_car));
        $this->assertSame([$other->id], $this->getHistoryOwners($other_car));
    }
}
