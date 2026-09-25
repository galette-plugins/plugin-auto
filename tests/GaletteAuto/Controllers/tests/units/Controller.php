<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\Controllers\tests\units;

use Analog\Analog;
use Galette\Entity\Adherent;
use Galette\Tests\GaletteRoutingTestCase;
use GaletteAuto\Auto;

/**
 * Vehicles controller tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Controller extends GaletteRoutingTestCase
{
    protected int $seed = 20260925101512;
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
            $object->value = $value;
            $this->assertTrue($object->store(true));
            $this->props[$property] = $object->id;
        }

        $model = new \GaletteAuto\Model($this->zdb);
        $this->assertTrue($model->check(['model' => '307', 'brand' => $this->props['brand']]));
        $this->assertTrue($model->store(true));
        $this->props['model'] = $model->id;
    }

    /**
     * Cleanup after each test method
     */
    public function tearDown(): void
    {
        $this->login->logout();
        parent::tearDown();
    }

    /**
     * Create a vehicle, bypassing controller
     *
     * @param int    $id_adh Owner ID
     * @param string $name   Vehicle name
     */
    private function createVehicle(int $id_adh, string $name = 'Titine'): int
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
        return (int)$this->zdb->execute($select)->current()[Auto::PK];
    }

    /**
     * Get vehicle name from database
     *
     * @param int $car_id Vehicle ID
     */
    private function getVehicleName(int $car_id): string
    {
        $select = $this->zdb->select(AUTO_PREFIX . Auto::TABLE);
        $select->where([Auto::PK => $car_id]);
        return $this->zdb->execute($select)->current()['car_name'];
    }

    /**
     * Build vehicle store request
     *
     * @param array<string,mixed> $data     Posted data
     * @param ?int                $car_id   Vehicle ID, null for a new one
     */
    private function storeRequest(array $data, ?int $car_id = null): \Slim\Psr7\Request
    {
        $request = $car_id === null
            ? $this->createRequest('doVehicleAdd', [], 'POST')
            : $this->createRequest('doVehicleEdit', ['id' => (string)$car_id], 'POST');
        return $request->withParsedBody(
            $data + [
                'registration' => 'GA-456-TE',
                'name' => 'Changed',
                'first_registration_date' => '2001-02-12',
                'first_circulation_date' => '2001-02-13',
                'fuel' => (string)Auto::FUEL_PETROL,
                'model' => (string)$this->props['model'],
                'color' => (string)$this->props['color'],
                'body' => (string)$this->props['body'],
                'finition' => (string)$this->props['finition'],
                'state' => (string)$this->props['state'],
                'transmission' => (string)$this->props['transmission'],
            ]
        );
    }

    /**
     * Log in given member
     *
     * @param array<string,mixed> $mdata Member data
     */
    private function logMember(array $mdata): void
    {
        $this->assertTrue($this->login->login($mdata['login_adh'], $mdata['mdp_adh']));
    }

    /**
     * Make member two manager of a group
     *
     * @param Adherent[] $members Group members
     */
    private function makeMemberTwoManager(array $members): void
    {
        $group = new \Galette\Entity\Group();
        $group->setName('Auto group');
        $this->assertTrue($group->store());
        $this->assertTrue($group->setManagers([$this->getMemberTwo()]));
        $this->assertTrue($group->setMembers($members));
    }

    /**
     * Assert access has been refused by the controller
     *
     * @param \Psr\Http\Message\ResponseInterface $test_response Response
     * @param string                              $log           Expected log message
     */
    private function expectAccessDenied(\Psr\Http\Message\ResponseInterface $test_response, string $log): void
    {
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('myVehiclesList')]],
            $test_response->getHeaders()
        );
        $this->assertSame(301, $test_response->getStatusCode());
        $this->expectFlashData(['error_detected' => ['You do not have enough privileges.']]);
        $this->expectLogEntry(Analog::WARNING, $log);
        $this->expectNoLogEntry();
    }

    /**
     * A group manager cannot list vehicles of a member outside its groups
     */
    public function testManagerCannotListOtherMemberVehicles(): void
    {
        $member_one = $this->getMemberOne();
        $member_two = $this->getMemberTwo();
        $this->makeMemberTwoManager([$member_two]);

        $this->logMember($this->dataAdherentTwo());
        $request = $this->createRequest('memberVehiclesList', ['id' => (string)$member_one->id]);
        $this->expectAccessDenied(
            $this->app->handle($request),
            'Trying to list vehicles of member #' . $member_one->id
        );
    }

    /**
     * A group manager can list vehicles of a member of its groups
     */
    public function testManagerListsManagedMemberVehicles(): void
    {
        $member_one = $this->getMemberOne();
        $this->makeMemberTwoManager([$member_one]);
        $this->createVehicle($member_one->id);

        $this->logMember($this->dataAdherentTwo());
        $request = $this->createRequest('memberVehiclesList', ['id' => (string)$member_one->id]);
        $test_response = $this->app->handle($request);
        $this->expectOK($test_response);
        $this->assertStringContainsString('Titine', (string)$test_response->getBody());
    }

    /**
     * A member cannot edit the vehicle of another member
     */
    public function testMemberCannotShowOtherVehicle(): void
    {
        $car_id = $this->createVehicle($this->getMemberOne()->id);
        $this->getMemberTwo();

        $this->logMember($this->dataAdherentTwo());
        $request = $this->createRequest('vehicleEdit', ['id' => (string)$car_id]);
        $this->expectAccessDenied(
            $this->app->handle($request),
            'Trying to edit vehicle #' . $car_id
        );
    }

    /**
     * An owner can edit its vehicle
     */
    public function testOwnerShowsVehicle(): void
    {
        $car_id = $this->createVehicle($this->getMemberOne()->id);

        $this->logMember($this->dataAdherentOne());
        $request = $this->createRequest('vehicleEdit', ['id' => (string)$car_id]);
        $test_response = $this->app->handle($request);
        $this->expectOK($test_response);
        $this->assertStringContainsString('Titine', (string)$test_response->getBody());
    }

    /**
     * Editing a vehicle that does not exist is refused
     */
    public function testShowMissingVehicle(): void
    {
        $this->logSuperAdmin();
        $request = $this->createRequest('vehicleEdit', ['id' => '999999']);
        $test_response = $this->app->handle($request);
        $this->expectLogEntry(Analog::ERROR, 'Cannot load car from id `999999`');
        $this->expectAccessDenied($test_response, 'Trying to edit vehicle #999999');
    }

    /**
     * A member cannot show history of the vehicle of another member
     */
    public function testMemberCannotShowOtherVehicleHistory(): void
    {
        $car_id = $this->createVehicle($this->getMemberOne()->id);
        $this->getMemberTwo();

        $this->logMember($this->dataAdherentTwo());
        $request = $this->createRequest('vehicleHistory', ['id' => (string)$car_id]);
        $this->expectAccessDenied(
            $this->app->handle($request),
            'Trying to show history of vehicle #' . $car_id
        );
    }

    /**
     * A member cannot get the removal confirmation of the vehicle of another member
     */
    public function testMemberCannotConfirmOtherVehicleRemoval(): void
    {
        $car_id = $this->createVehicle($this->getMemberOne()->id);
        $this->getMemberTwo();

        $this->logMember($this->dataAdherentTwo());
        $request = $this->createRequest('removeVehicle', ['id' => (string)$car_id]);
        $this->expectAccessDenied(
            $this->app->handle($request),
            'Trying to remove vehicle #' . $car_id
        );
    }

    /**
     * Every vehicle of a batch removal is checked, not only the first one
     */
    public function testMemberCannotConfirmBatchRemovalWithOtherVehicle(): void
    {
        $member_two = $this->getMemberTwo();
        $own_id = $this->createVehicle($member_two->id, 'Mine');
        $other_id = $this->createVehicle($this->getMemberOne()->id);

        $this->logMember($this->dataAdherentTwo());
        $request = $this->createRequest('removeVehicles', [], 'POST');
        $request = $request->withParsedBody(['entries_sel' => [(string)$own_id, (string)$other_id]]);
        $this->expectAccessDenied(
            $this->app->handle($request),
            'Trying to remove vehicles #' . $own_id . ', #' . $other_id
        );

        //own vehicles only is fine
        $request = $this->createRequest('removeVehicles', [], 'POST');
        $request = $request->withParsedBody(['entries_sel' => [(string)$own_id]]);
        $this->expectOK($this->app->handle($request));
    }

    /**
     * A member cannot modify the vehicle of another member
     */
    public function testMemberCannotStoreOtherVehicle(): void
    {
        $car_id = $this->createVehicle($this->getMemberOne()->id);
        $this->getMemberTwo();

        $this->logMember($this->dataAdherentTwo());
        $this->expectAccessDenied(
            $this->app->handle($this->storeRequest([], $car_id)),
            'Trying to store vehicle #' . $car_id
        );
        $this->assertSame('Titine', $this->getVehicleName($car_id));
    }

    /**
     * Stored vehicle is the one from the route, not the posted one
     */
    public function testStoreUsesRouteVehicle(): void
    {
        $member_two = $this->getMemberTwo();
        $own_id = $this->createVehicle($member_two->id, 'Mine');
        $other_id = $this->createVehicle($this->getMemberOne()->id);

        $this->logMember($this->dataAdherentTwo());
        $test_response = $this->app->handle(
            $this->storeRequest([Auto::PK => (string)$other_id], $own_id)
        );
        $this->assertSame(301, $test_response->getStatusCode());
        $this->expectFlashData(['success_detected' => ['Vehicle has been saved!']]);
        $this->assertSame('Titine', $this->getVehicleName($other_id));
        $this->assertSame('Changed', $this->getVehicleName($own_id));
    }
}
