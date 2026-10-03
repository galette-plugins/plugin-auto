<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto;

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
        parent::__construct($id_adh);
    }

    /**
     * Gets the default picture to show, anyway
     *
     * @see Logo::getDefaultPicture()
     */
    protected function getDefaultPicture(): void
    {
        $this->format = 'png';
        $this->mime = 'image/png';
        $this->has_picture = false;
        $this->setDefaultPath(
            $this->plugins->getTemplatesPathFromName('Galette Auto')
            . '/../../webroot/images/1f698.png'
        );
    }
}
