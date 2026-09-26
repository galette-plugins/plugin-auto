<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\Controllers\tests\units;

use Galette\Tests\GaletteRoutingTestCase;
use GaletteAuto\Brand;
use GaletteAuto\Model;

/**
 * Models controller tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class ModelsController extends GaletteRoutingTestCase
{
    protected int $seed = 20260925111512;
    protected bool $load_plugins = true;

    private int $brand_id;

    /**
     * Set up tests
     */
    public function setUp(): void
    {
        parent::setUp();
        $brand = new Brand($this->zdb);
        $brand->setValue('Peugeot');
        $this->assertTrue($brand->store(true));
        $this->brand_id = $brand->getId();
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
     * Create a model, bypassing controller
     *
     * @param string $name Model name
     */
    private function createModel(string $name): int
    {
        $model = new Model($this->zdb);
        $this->assertTrue($model->check(['model' => $name, 'brand' => $this->brand_id]));
        $this->assertTrue($model->store(true));
        return $model->id;
    }

    /**
     * Stored model is the one from the route, not the posted one
     */
    public function testEditUsesRouteId(): void
    {
        $first = $this->createModel('307');
        $second = $this->createModel('308');

        $this->logSuperAdmin();
        $request = $this->createRequest('doModelEdit', ['id' => (string)$first], 'POST')
            ->withParsedBody([Model::PK => (string)$second, 'model' => '306', 'brand' => (string)$this->brand_id]);
        $test_response = $this->app->handle($request);
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('modelsList')]],
            $test_response->getHeaders()
        );
        $this->expectFlashData(['success_detected' => ['Model has been saved!']]);
        $this->assertSame('306', (new Model($this->zdb, $first))->model);
        $this->assertSame('308', (new Model($this->zdb, $second))->model);
    }

    /**
     * Posted values are shown back after a failed edition
     */
    public function testEditErrorKeepsPostedValues(): void
    {
        $id = $this->createModel('307');

        $this->logSuperAdmin();
        $request = $this->createRequest('doModelEdit', ['id' => (string)$id], 'POST')
            ->withParsedBody(['model' => 'Posted model', 'brand' => '-1']);
        $test_response = $this->app->handle($request);
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('modelEdit', ['id' => (string)$id])]],
            $test_response->getHeaders()
        );
        $this->expectFlashData(['error_detected' => ['- You must select a brand!']]);

        $request = $this->createRequest('modelEdit', ['id' => (string)$id]);
        $test_response = $this->app->handle($request);
        $this->assertSame(200, $test_response->getStatusCode());
        $this->assertStringContainsString('value="Posted model"', (string)$test_response->getBody());
        $this->assertSame('307', (new Model($this->zdb, $id))->model);
    }

    /**
     * Make member one manager of a group
     */
    private function makeMemberOneManager(): void
    {
        $member_one = $this->getMemberOne();
        $group = new \Galette\Entity\Group();
        $group->setName('Auto group');
        $this->assertTrue($group->store());
        $this->assertTrue($group->setManagers([$member_one]));
    }

    /**
     * Create a vehicle of given model, bypassing controller
     *
     * @param int $model_id Model ID
     */
    private function createVehicle(int $model_id): void
    {
        $values = [];
        foreach (['Body', 'Color', 'Finition', 'State', 'Transmission'] as $property) {
            $class = '\\GaletteAuto\\' . $property;
            $object = new $class($this->zdb);
            $object->setValue('Test ' . $property);
            $this->assertTrue($object->store(true));
            $values[$class::PK] = $object->getId();
        }
        $insert = $this->zdb->insert(AUTO_PREFIX . \GaletteAuto\Auto::TABLE);
        $insert->values($values + [
            'car_name' => 'Titine',
            'car_registration' => 'GA-123-TE',
            'car_first_registration_date' => '2001-02-12',
            'car_first_circulation_date' => '2001-02-13',
            'car_creation_date' => date('Y-m-d'),
            Model::PK => $model_id,
            \Galette\Entity\Adherent::PK => $this->getMemberOne()->id,
        ]);
        $this->zdb->execute($insert);
    }

    /**
     * Models list and forms
     */
    public function testListAndForms(): void
    {
        $this->createModel('308');
        $this->createModel('207');

        $this->logSuperAdmin();
        $test_response = $this->app->handle($this->createRequest('modelsList'));
        $this->expectOK($test_response);
        $body = (string)$test_response->getBody();
        $this->assertLessThan(strpos($body, '308'), strpos($body, '207'));
        $this->assertStringContainsString('2 models', $body);

        $request = $this->createRequest('modelAdd', [], 'GET', 'text/html', ['brand' => (string)$this->brand_id]);
        $test_response = $this->app->handle($request);
        $this->expectOK($test_response);
        $this->assertStringContainsString('New model', (string)$test_response->getBody());

        $request = $this->createRequest('doModelAdd', [], 'POST')
            ->withParsedBody(['model' => '406', 'brand' => (string)$this->brand_id]);
        $test_response = $this->app->handle($request);
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('modelsList')]],
            $test_response->getHeaders()
        );
        $this->expectFlashData(['success_detected' => ['New model has been added!']]);
    }

    /**
     * Group managers manage models, but cannot remove them
     */
    public function testGroupManagerAccess(): void
    {
        $id = $this->createModel('307');
        $this->makeMemberOneManager();
        $mdata = $this->dataAdherentOne();
        $this->assertTrue($this->login->login($mdata['login_adh'], $mdata['mdp_adh']));

        $this->expectOK($this->app->handle($this->createRequest('modelsList')));
        $this->expectOK($this->app->handle($this->createRequest('modelEdit', ['id' => (string)$id])));
        $this->expectAuthMiddlewareRefused(
            $this->app->handle($this->createRequest('removeModel', ['id' => (string)$id]))
        );
    }

    /**
     * Simple members cannot manage models
     */
    public function testMemberAccess(): void
    {
        $this->getMemberOne();
        $mdata = $this->dataAdherentOne();
        $this->assertTrue($this->login->login($mdata['login_adh'], $mdata['mdp_adh']));
        $this->expectAuthMiddlewareRefused($this->app->handle($this->createRequest('modelsList')));
    }

    /**
     * Remove models
     */
    public function testRemove(): void
    {
        $unused = $this->createModel('307');
        $used = $this->createModel('308');
        $this->createVehicle($used);

        $this->logSuperAdmin();
        $test_response = $this->app->handle($this->createRequest('removeModel', ['id' => (string)$unused]));
        $this->expectOK($test_response);
        $this->assertStringContainsString('Remove model &quot;307&quot;', (string)$test_response->getBody());

        $request = $this->createRequest('doRemoveModel', ['id' => (string)$unused], 'POST')
            ->withParsedBody(['id' => (string)$unused, 'confirm' => '1']);
        $test_response = $this->app->handle($request);
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('modelsList')]],
            $test_response->getHeaders()
        );
        $this->expectFlashData(['success_detected' => ['Successfully deleted!']]);
        $this->assertFalse((new Model($this->zdb))->load($unused));
        $this->expectLogEntry(\Analog\Analog::ERROR, 'Cannot load model from id `' . $unused . '`');

        //used model cannot be removed; last check, pgsql aborts the transaction
        $request = $this->createRequest('doRemoveModel', ['id' => (string)$used], 'POST')
            ->withParsedBody(['id' => (string)$used, 'confirm' => '1']);
        $this->app->handle($request);
        $this->expectFlashData(['error_detected' => ['This model is used by one or more vehicles, it cannot be deleted.']]);
        $this->expectLogEntry(\Analog\Analog::ERROR, 'Query error: DELETE FROM');
        $this->expectLogEntry(\Analog\Analog::WARNING, 'Cannot delete models from ids `' . $used . '`');
        $this->expectNoLogEntry();
        if (!$this->zdb->isPostgres()) {
            $this->expected_mysql_warnings[] = new \ArrayObject([
                'Level' => 'Error',
                'Code' => 1451,
                'Message' => 'regex:/^Cannot delete or update a parent row: a foreign key constraint fails/',
            ]);
        }
    }
}
