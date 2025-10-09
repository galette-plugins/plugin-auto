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

use Analog\Analog;
use Galette\Core\Picture as GalettePicture;
use Galette\Core\Plugins;

/**
 * Vehicle picture handling
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class Picture extends GalettePicture
{
    private Plugins $plugins;

    protected string $tbl_prefix = AUTO_PREFIX;
    public const PK = Auto::PK;

    /**
     * Default constructor.
     *
     * @param Plugins    $plugins Plugins
     * @param mixed|null $id_adh  ID of the member
     */
    public function __construct(Plugins $plugins, mixed $id_adh = null)
    {
        $this->plugins = $plugins;
        $this->store_path = GALETTE_PHOTOS_PATH . '/auto_photos/';
        if (!file_exists($this->store_path)) {
            if (!mkdir($this->store_path)) {
                Analog::log(
                    'Unable to create photo dir `' . $this->store_path . '`.',
                    Analog::ERROR
                );
            } else {
                Analog::log(
                    'New directory `' . $this->store_path . '` has been created',
                    Analog::INFO
                );
            }
        } elseif (!is_dir($this->store_path)) {
            Analog::log(
                'Unable to store plugin images, since `' . $this->store_path
                . '` is not a directory.',
                Analog::WARNING
            );
        }
        parent::__construct($id_adh);
    }

    /**
     * Gets the default picture to show, anyway
     *
     * @see Logo::getDefaultPicture()
     *
     * @return void
     */
    protected function getDefaultPicture(): void
    {
        $this->file_path = (string)realpath(
            $this->plugins->getTemplatesPathFromName('Galette Auto')
            . '/../../webroot/images/1f698.png'
        );
        $this->format = 'png';
        $this->mime = 'image/png';
        $this->has_picture = false;
    }
}
