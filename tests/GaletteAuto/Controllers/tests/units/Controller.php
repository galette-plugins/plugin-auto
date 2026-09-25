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
     * @param array<string,mixed> $data   Posted data
     * @param ?int                $car_id Vehicle ID, null for a new one
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

    /**
     * Count vehicles in database
     */
    private function countVehicles(): int
    {
        $select = $this->zdb->select(AUTO_PREFIX . Auto::TABLE);
        return $this->zdb->execute($select)->count();
    }

    /**
     * A member cannot remove the vehicle of another member
     */
    public function testMemberCannotRemoveOtherVehicle(): void
    {
        $member_two = $this->getMemberTwo();
        $own_id = $this->createVehicle($member_two->id, 'Mine');
        $other_id = $this->createVehicle($this->getMemberOne()->id);

        $this->logMember($this->dataAdherentTwo());
        $request = $this->createRequest('doRemoveVehicle', [], 'POST')->withParsedBody([
            'id' => [(string)$own_id, (string)$other_id],
            'confirm' => '1',
            'redirect_uri' => $this->routeparser->urlFor('myVehiclesList'),
        ]);
        $this->expectAccessDenied(
            $this->app->handle($request),
            'Trying to remove vehicles #' . $own_id . ', #' . $other_id
        );
        $this->assertSame(2, $this->countVehicles());

        //own vehicle can be removed
        $request = $this->createRequest('doRemoveVehicle', ['id' => (string)$own_id], 'POST')->withParsedBody([
            'id' => (string)$own_id,
            'confirm' => '1',
            'redirect_uri' => $this->routeparser->urlFor('myVehiclesList'),
        ]);
        $test_response = $this->app->handle($request);
        $this->assertSame(301, $test_response->getStatusCode());
        $this->expectFlashData(['success_detected' => ['1 vehicles have been successfully deleted.']]);
        $this->assertSame(1, $this->countVehicles());
    }

    /**
     * Get vehicle owner from database
     *
     * @param ?int $car_id Vehicle ID, last one if null
     */
    private function getVehicleOwner(?int $car_id = null): int
    {
        $select = $this->zdb->select(AUTO_PREFIX . Auto::TABLE);
        if ($car_id !== null) {
            $select->where([Auto::PK => $car_id]);
        } else {
            $select->order(Auto::PK . ' DESC')->limit(1);
        }
        return (int)$this->zdb->execute($select)->current()[Adherent::PK];
    }

    /**
     * A simple member always creates vehicles for itself
     */
    public function testMemberCreatesVehicleForItself(): void
    {
        $member_one = $this->getMemberOne();
        $member_two = $this->getMemberTwo();

        $this->logMember($this->dataAdherentOne());
        $test_response = $this->app->handle(
            $this->storeRequest(['owner_id' => (string)$member_two->id, 'change_owner' => '1'])
        );
        $this->assertSame(301, $test_response->getStatusCode());
        $this->expectFlashData(['success_detected' => ['Vehicle has been saved!']]);
        $this->assertSame(1, $this->countVehicles());
        $this->assertSame($member_one->id, $this->getVehicleOwner());
    }

    /**
     * A simple member cannot give its vehicle to another member
     */
    public function testMemberCannotChangeOwner(): void
    {
        $member_one = $this->getMemberOne();
        $member_two = $this->getMemberTwo();
        $car_id = $this->createVehicle($member_one->id);

        $this->logMember($this->dataAdherentOne());
        $test_response = $this->app->handle(
            $this->storeRequest(['owner_id' => (string)$member_two->id, 'change_owner' => '1'], $car_id)
        );
        $this->assertSame(301, $test_response->getStatusCode());
        $this->expectFlashData(['success_detected' => ['Vehicle has been saved!']]);
        $this->assertSame($member_one->id, $this->getVehicleOwner($car_id));
    }

    /**
     * A group manager cannot attach a vehicle to a member outside its groups
     */
    public function testManagerCannotAttachToOtherMember(): void
    {
        $member_one = $this->getMemberOne();
        $member_two = $this->getMemberTwo();
        $this->makeMemberTwoManager([$member_two]);

        $this->logMember($this->dataAdherentTwo());
        $test_response = $this->app->handle(
            $this->storeRequest(['owner_id' => (string)$member_one->id, 'change_owner' => '1'])
        );
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('vehicleAdd')]],
            $test_response->getHeaders()
        );
        $this->expectFlashData(['error_detected' => ['- you cannot attach this car to this member']]);
        $this->expectLogEntry(
            Analog::WARNING,
            'Trying to attach vehicle to member #' . $member_one->id . ' (user #' . $member_two->id . ')'
        );
        $this->assertSame(0, $this->countVehicles());
    }

    /**
     * A group manager can attach a vehicle to a member of its groups
     */
    public function testManagerAttachesToManagedMember(): void
    {
        $member_one = $this->getMemberOne();
        $this->makeMemberTwoManager([$member_one]);

        $this->logMember($this->dataAdherentTwo());
        $test_response = $this->app->handle(
            $this->storeRequest(['owner_id' => (string)$member_one->id, 'change_owner' => '1'])
        );
        $this->assertSame(301, $test_response->getStatusCode());
        $this->expectFlashData(['success_detected' => ['Vehicle has been saved!']]);
        $this->assertSame($member_one->id, $this->getVehicleOwner());
    }

    /**
     * Staff can change owner of a vehicle
     */
    public function testAdminChangesOwner(): void
    {
        $member_two = $this->getMemberTwo();
        $car_id = $this->createVehicle($this->getMemberOne()->id);

        $this->logSuperAdmin();
        $test_response = $this->app->handle(
            $this->storeRequest(['owner_id' => (string)$member_two->id, 'change_owner' => '1'], $car_id)
        );
        $this->assertSame(301, $test_response->getStatusCode());
        $this->expectFlashData(['success_detected' => ['Vehicle has been saved!']]);
        $this->assertSame($member_two->id, $this->getVehicleOwner($car_id));
    }

    /**
     * Store a picture for a vehicle, bypassing controller
     *
     * @param int $car_id Vehicle ID
     *
     * @return string Picture checksum
     */
    private function storePicture(int $car_id): string
    {
        $content = file_get_contents(GALETTE_ROOT . '../tests/fixtures/galette_pro.png');
        $insert = $this->zdb->insert(AUTO_PREFIX . \GaletteAuto\Picture::TABLE);
        $insert->values([
            Auto::PK => ':' . Auto::PK,
            'picture' => ':picture',
            'format' => ':format',
        ]);
        //binary content must be sent as a LOB for PostgreSQL
        $stmt = $this->zdb->sql->prepareStatementForSqlObject($insert);
        $container = $stmt->getParameterContainer();
        $container->offsetSet('picture', ':picture', $container::TYPE_LOB);
        $stmt->setParameterContainer($container);
        $stmt->execute([Auto::PK => $car_id, 'picture' => $content, 'format' => 'png']);
        return md5($content);
    }

    /**
     * Get vehicle photo checksum as current user
     *
     * @param int $car_id Vehicle ID
     */
    private function getPhoto(int $car_id): string
    {
        $request = $this->createRequest('vehiclePhoto', ['id' => (string)$car_id]);
        $test_response = $this->app->handle($request);
        $this->assertSame(200, $test_response->getStatusCode());
        return md5((string)$test_response->getBody());
    }

    /**
     * Vehicle photo is only served to users allowed to see the vehicle
     */
    public function testVehiclePhoto(): void
    {
        $default = md5_file(__DIR__ . '/../../../../../webroot/images/1f698.png');
        $member_one = $this->getMemberOne();
        $member_two = $this->getMemberTwo();
        $car_id = $this->createVehicle($member_two->id);
        $content = $this->storePicture($car_id);
        $public_car_id = $this->createVehicle($member_one->id, 'Public');
        $public_content = $this->storePicture($public_car_id);
        $this->assertNotSame($default, $content);

        try {
            //public pages are disabled
            $this->assertSame($default, $this->getPhoto($car_id));
            $this->assertSame($default, $this->getPhoto($public_car_id));

            //another member
            $this->logMember($this->dataAdherentOne());
            $this->assertSame($default, $this->getPhoto($car_id));
            $this->login->logout();

            //owner
            $this->logMember($this->dataAdherentTwo());
            $this->assertSame($content, $this->getPhoto($car_id));
            $this->login->logout();

            //public pages: member one appears in public list once up to date
            $this->setRawPreference('pref_bool_publicpages', true);
            $this->setRawPreference(
                'pref_publicpages_visibility_generic',
                \Galette\Enums\PublicPageVisibility::Everyone->value
            );
            $this->assertSame($default, $this->getPhoto($public_car_id));
            $this->logSuperAdmin();
            $this->getMemberOne();
            $this->createContrib($this->getContribData());
            $this->login->logout();
            $this->assertSame($public_content, $this->getPhoto($public_car_id));
            //member two does not appear in public list
            $this->assertSame($default, $this->getPhoto($car_id));
            $this->expectNoLogEntry();
        } finally {
            foreach ([$car_id, $public_car_id] as $id) {
                $file = GALETTE_PHOTOS_PATH . '/auto_photos/' . $id . '.png';
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Owner and managers of its groups can show vehicle history
     */
    public function testShowHistory(): void
    {
        $member_one = $this->getMemberOne();
        $this->makeMemberTwoManager([$member_one]);
        $car_id = $this->createVehicle($member_one->id);

        foreach ([$this->dataAdherentOne(), $this->dataAdherentTwo()] as $mdata) {
            $this->logMember($mdata);
            $request = $this->createRequest('vehicleHistory', ['id' => (string)$car_id]);
            $test_response = $this->app->handle($request);
            $this->expectOK($test_response);
            $this->assertStringContainsString('History of car #' . $car_id, (string)$test_response->getBody());
            $this->login->logout();
        }
    }

    /**
     * Vehicles list filters are kept in session
     */
    public function testFilter(): void
    {
        $this->getMemberOne();
        $this->logMember($this->dataAdherentOne());
        $request = $this->createRequest('vehiclesFilter', [], 'POST')->withParsedBody(['nbshow' => '10']);
        $test_response = $this->app->handle($request);
        //simple members cannot access the full list
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('myVehiclesList')]],
            $test_response->getHeaders()
        );
        $this->assertSame(10, $this->session->vehicles_filters->show);

        $this->login->logout();
        $this->logSuperAdmin();
        $request = $this->createRequest('vehiclesFilter', [], 'POST')->withParsedBody(['clear_filter' => '1']);
        $test_response = $this->app->handle($request);
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('vehiclesList')]],
            $test_response->getHeaders()
        );
        $this->assertSame((int)$this->preferences->pref_numrows, $this->session->vehicles_filters->show);
    }
}
