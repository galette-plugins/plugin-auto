<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
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
    public const string PK = Auto::PK;

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
