<?php

/**
 * Copyright © 2003-2026 The Galette Team
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

namespace GaletteAuto\Filters;

use Galette\Core\Pagination;
use Laminas\Db\Sql\Select;

/**
 * Properties list filters and paginator
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */

class PropertiesList extends Pagination
{
    /**
     * Returns the field we want to default set order to
     *
     * @return string field name
     */
    protected function getDefaultOrder(): string
    {
        return 'field';
    }

    /**
     * Add SQL limit
     *
     * @param Select $select Original select
     */
    public function setLimit(Select $select): self
    {
        $this->setLimits($select);
        return $$this;
    }
}
