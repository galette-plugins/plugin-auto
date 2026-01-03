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

namespace GaletteAuto\tests\units;

use Galette\Tests\GaletteTestCase;

/**
 * Model tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Model extends GaletteTestCase
{
    protected int $seed = 20240130141727;

    /**
     * Cleanup after each test method
     */
    public function tearDown(): void
    {
        $delete = $this->zdb->delete(AUTO_PREFIX . \GaletteAuto\Model::TABLE);
        $this->zdb->execute($delete);

        $delete = $this->zdb->delete(AUTO_PREFIX . \GaletteAuto\Brand::TABLE);
        $this->zdb->execute($delete);

        parent::tearDown();
    }

    /**
     * Test add and update
     */
    public function testCrud(): void
    {
        $brand = new \GaletteAuto\Brand($this->zdb);
        //Add new brand
        $brand->value = 'Audi';
        $this->assertTrue($brand->store(true));
        $first_brand_id = $brand->id;

        //add another brand
        $brand = new \GaletteAuto\Brand($this->zdb);
        $brand->value = 'Mercedes';
        $this->assertTrue($brand->store(true));
        $second_brand_id = $brand->id;

        $this->assertCount(2, $brand->getList());

        $models = new \GaletteAuto\Repository\Models(
            $this->zdb,
            $this->preferences,
            $this->login,
            new \GaletteAuto\Filters\ModelsList()
        );
        $this->assertCount(0, $models->getList());

        $model = new \GaletteAuto\Model($this->zdb);
        $data = [];
        $this->assertFalse($model->check($data));
        $this->assertSame(
            [
                "- You must select a brand!",
                "- You must provide a value!"
            ],
            $model->getErrors()
        );

        $data = [
            'model' => 'A3'
        ];
        $this->assertFalse($model->check($data));
        $this->assertSame(
            [
                "- You must select a brand!"
            ],
            $model->getErrors()
        );

        $data = [
            'brand' => $first_brand_id
        ];
        $this->assertFalse($model->check($data));
        $this->assertSame(
            [
                "- You must provide a value!"
            ],
            $model->getErrors()
        );

        $data = [
            'model' => 'A3',
            'brand' => $first_brand_id,
        ];
        $this->assertTrue($model->check($data));
        $this->assertTrue($model->store(true));

        $this->assertCount(1, $models->getList());
        $this->assertCount(1, $models->getList($first_brand_id));
        $this->assertCount(0, $models->getList($second_brand_id));

        $model = new \GaletteAuto\Model($this->zdb);
        $data = [
            'model' => 'A',
            'brand' => $first_brand_id,
        ];
        $this->assertTrue($model->check($data));
        $this->assertTrue($model->store(true));
        $id_model = $model->id;

        $this->assertCount(2, $models->getList());

        $data = [
            'model' => 'A5',
            'brand' => $first_brand_id
        ];
        $this->assertTrue($model->check($data));
        $this->assertTrue($model->store());

        $this->assertCount(2, $models->getList());
        $this->assertCount(2, $models->getList($first_brand_id));
        $this->assertCount(0, $models->getList($second_brand_id));

        $model = new \GaletteAuto\Model($this->zdb);
        $data = [
            'model' => 'Class S',
            'brand' => $second_brand_id,
        ];
        $this->assertTrue($model->check($data));
        $this->assertTrue($model->store(true));

        $this->assertCount(3, $models->getList());
        $this->assertCount(2, $models->getList($first_brand_id));
        $this->assertCount(1, $models->getList($second_brand_id));

        $model = new \GaletteAuto\Model($this->zdb);
        $this->assertTrue($model->delete([$id_model]));

        $this->assertCount(2, $models->getList());
        $this->assertCount(1, $models->getList($first_brand_id));
        $this->assertCount(1, $models->getList($second_brand_id));
    }

    /**
     * Test load error
     */
    public function testLoadError(): void
    {
        $brand = new \GaletteAuto\Model($this->zdb);
        $this->expectNoLogEntry();
        $this->assertFalse($brand->load(999));
        $this->expectLogEntry(
            \Analog::ERROR,
            '[GaletteAuto\Model] Cannot load model from id `999`'
        );
    }
}
