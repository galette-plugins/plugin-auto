<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\Controllers\tests\units;

use Galette\Tests\GaletteRoutingTestCase;
use GaletteAuto\Color;

/**
 * Properties controller tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class PropertiesController extends GaletteRoutingTestCase
{
    protected int $seed = 20260925111034;
    protected bool $load_plugins = true;

    /**
     * Cleanup after each test method
     */
    public function tearDown(): void
    {
        $this->login->logout();
        parent::tearDown();
    }

    /**
     * Create a color, bypassing controller
     *
     * @param string $value Color name
     */
    private function createColor(string $value): int
    {
        $color = new Color($this->zdb);
        $color->setValue($value);
        $this->assertTrue($color->store(true));
        return $color->getId();
    }

    /**
     * Get color name from database
     *
     * @param int $id Color ID
     */
    private function getColor(int $id): string
    {
        $color = new Color($this->zdb);
        $this->assertTrue($color->load($id));
        return $color->getValue();
    }

    /**
     * Stored property is the one from the route, not the posted one
     */
    public function testEditUsesRouteId(): void
    {
        $red = $this->createColor('Red');
        $blue = $this->createColor('Blue');

        $this->logSuperAdmin();
        $request = $this->createRequest(
            'doPropertyEdit',
            ['property' => 'color', 'id' => (string)$red],
            'POST'
        )->withParsedBody([Color::PK => (string)$blue, 'color' => 'Dark red']);
        $test_response = $this->app->handle($request);
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('colorsList')]],
            $test_response->getHeaders()
        );
        $this->expectFlashData(['success_detected' => ['Color has been saved!']]);
        $this->assertSame('Dark red', $this->getColor($red));
        $this->assertSame('Blue', $this->getColor($blue));
    }

    /**
     * Adding an empty property goes back to add form
     */
    public function testAddError(): void
    {
        $this->logSuperAdmin();
        $request = $this->createRequest('doPropertyAdd', ['property' => 'color'], 'POST')
            ->withParsedBody(['color' => '']);
        $test_response = $this->app->handle($request);
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('propertyAdd', ['property' => 'color'])]],
            $test_response->getHeaders()
        );
        $this->expectFlashData(['error_detected' => ['- You must provide a value!']]);
        //posted value is kept in session, not the entity
        $this->assertSame('', $this->session->auto_color_data);

        $test_response = $this->app->handle($this->createRequest('propertyAdd', ['property' => 'color']));
        $this->expectOK($test_response);
        $this->assertFalse(isset($this->session->auto_color_data));
    }

    /**
     * Log in member one
     */
    private function logMemberOne(): void
    {
        $this->getMemberOne();
        $mdata = $this->dataAdherentOne();
        $this->assertTrue($this->login->login($mdata['login_adh'], $mdata['mdp_adh']));
    }

    /**
     * Every property list, sorted by value
     */
    public function testLists(): void
    {
        $routes = [
            'body' => 'bodiesList',
            'brand' => 'brandsList',
            'color' => 'colorsList',
            'finition' => 'finitionsList',
            'state' => 'statesList',
            'transmission' => 'transmissionsList',
        ];
        $this->logSuperAdmin();
        foreach ($routes as $property => $route) {
            $class = \GaletteAuto\AbstractObject::getClassForPropName($property);
            foreach (['Zeta ' . $property, 'Alpha ' . $property] as $value) {
                $object = new $class($this->zdb);
                $object->setValue($value);
                $this->assertTrue($object->store(true));
            }

            $test_response = $this->app->handle($this->createRequest($route));
            $this->expectOK($test_response);
            $body = (string)$test_response->getBody();
            $this->assertNotFalse(strpos($body, 'Zeta ' . $property), $property);
            $this->assertLessThan(strpos($body, 'Zeta ' . $property), strpos($body, 'Alpha ' . $property), $property);

            $test_response = $this->app->handle($this->createRequest('propertyAdd', ['property' => $property]));
            $this->expectOK($test_response);
        }
    }

    /**
     * Properties are managed by staff only
     */
    public function testMemberAccess(): void
    {
        $id = $this->createColor('Red');
        $this->logMemberOne();
        $this->expectAuthMiddlewareRefused($this->app->handle($this->createRequest('colorsList')));
        $this->expectAuthMiddlewareRefused(
            $this->app->handle($this->createRequest('propertyEdit', ['property' => 'color', 'id' => (string)$id]))
        );
    }

    /**
     * Edit form and brand page
     */
    public function testEditAndShow(): void
    {
        $id = $this->createColor('Red');
        $brand = new \GaletteAuto\Brand($this->zdb);
        $brand->setValue('Peugeot');
        $this->assertTrue($brand->store(true));
        $model = new \GaletteAuto\Model($this->zdb);
        $this->assertTrue($model->check(['model' => '307', 'brand' => $brand->getId()]));
        $this->assertTrue($model->store(true));

        $this->logSuperAdmin();
        $test_response = $this->app->handle(
            $this->createRequest('propertyEdit', ['property' => 'color', 'id' => (string)$id])
        );
        $this->expectOK($test_response);
        $this->assertStringContainsString('value="Red"', (string)$test_response->getBody());

        $test_response = $this->app->handle(
            $this->createRequest('propertyShow', ['property' => 'brand', 'id' => (string)$brand->getId()])
        );
        $this->expectOK($test_response);
        $body = (string)$test_response->getBody();
        $this->assertStringContainsString('Peugeot', $body);
        $this->assertStringContainsString('307', $body);
    }

    /**
     * Filters are kept in session
     */
    public function testFilter(): void
    {
        $this->logSuperAdmin();
        $request = $this->createRequest('propertyFilter', ['property' => 'color'], 'POST')
            ->withParsedBody(['nbshow' => '10']);
        $test_response = $this->app->handle($request);
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('colorsList')]],
            $test_response->getHeaders()
        );
        $this->assertSame(10, $this->session->filter_autocolor->show);

        $request = $this->createRequest('propertyFilter', ['property' => 'color'], 'POST')
            ->withParsedBody(['clear_filter' => '1']);
        $this->app->handle($request);
        $this->assertSame((int)$this->preferences->pref_numrows, $this->session->filter_autocolor->show);
    }

    /**
     * Remove properties
     */
    public function testRemove(): void
    {
        $unused = $this->createColor('Red');
        $used = $this->createColor('Blue');
        $values = [Color::PK => $used];
        foreach (['Body', 'Finition', 'State', 'Transmission'] as $property) {
            $class = '\\GaletteAuto\\' . $property;
            $object = new $class($this->zdb);
            $object->setValue('Test ' . $property);
            $this->assertTrue($object->store(true));
            $values[$class::PK] = $object->getId();
        }
        $brand = new \GaletteAuto\Brand($this->zdb);
        $brand->setValue('Peugeot');
        $this->assertTrue($brand->store(true));
        $model = new \GaletteAuto\Model($this->zdb);
        $this->assertTrue($model->check(['model' => '307', 'brand' => $brand->getId()]));
        $this->assertTrue($model->store(true));
        $insert = $this->zdb->insert(AUTO_PREFIX . \GaletteAuto\Auto::TABLE);
        $insert->values($values + [
            'car_name' => 'Titine',
            'car_registration' => 'GA-123-TE',
            'car_first_registration_date' => '2001-02-12',
            'car_first_circulation_date' => '2001-02-13',
            'car_creation_date' => date('Y-m-d'),
            \GaletteAuto\Model::PK => $model->getId(),
            \Galette\Entity\Adherent::PK => $this->getMemberOne()->id,
        ]);
        $this->zdb->execute($insert);

        $this->logSuperAdmin();
        $test_response = $this->app->handle(
            $this->createRequest('removeProperty', ['property' => 'color', 'id' => (string)$unused])
        );
        $this->expectOK($test_response);
        $this->assertStringContainsString('Remove Color Red', (string)$test_response->getBody());

        $request = $this->createRequest('doRemoveProperty', ['property' => 'color', 'id' => (string)$unused], 'POST')
            ->withParsedBody([
                'id' => (string)$unused,
                'confirm' => '1',
                'redirect_uri' => $this->routeparser->urlFor('colorsList'),
            ]);
        $test_response = $this->app->handle($request);
        $this->assertSame(
            ['Location' => [$this->routeparser->urlFor('colorsList')]],
            $test_response->getHeaders()
        );
        $this->expectFlashData(['success_detected' => ['1 color has been successfully deleted.']]);

        //used color cannot be removed; last check, pgsql aborts the transaction
        $request = $this->createRequest('doRemoveProperty', ['property' => 'color', 'id' => (string)$used], 'POST')
            ->withParsedBody([
                'id' => (string)$used,
                'confirm' => '1',
                'redirect_uri' => $this->routeparser->urlFor('colorsList'),
            ]);
        $this->app->handle($request);
        $this->expectFlashData(
            ['error_detected' => ['This color is used by one or more vehicles, it cannot be deleted.']]
        );
        $this->expectLogEntry(\Analog\Analog::ERROR, 'Query error: DELETE FROM');
        $this->expectLogEntry(\Analog\Analog::ERROR, 'Cannot remove colors #' . $used . ' |');
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
