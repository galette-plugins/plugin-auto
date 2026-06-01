<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto\Filters;

use Galette\Core\Pagination;

/**
 * Models list filters and paginator
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */

class ModelsList extends Pagination
{
    public const int ORDERBY_MODEL = 0;
    public const int ORDERBY_BRAND = 1;

    /**
     * Returns the field we want to default set order to
     *
     * @return string field name
     */
    protected function getDefaultOrder(): string
    {
        return 'model';
    }
}
