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
 * Autos list filters and paginator
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */

class AutosList extends Pagination
{
    /**
     * Returns the field we want to default set order to
     *
     * @return string field name
     */
    protected function getDefaultOrder(): string
    {
        return 'car_name';
    }

    /**
     * Build href
     *
     * @param int $page Page
     */
    protected function getHref(int $page): string
    {
        $args = [
            'option'    => 'page',
            'value'     => (string)$page
        ];

        if ($this->view->getEnvironment()->getGlobals()['cur_subroute']) {
            $args['type'] = $this->view->getEnvironment()->getGlobals()['cur_subroute'];
        }

        if ($this->view->getEnvironment()->getGlobals()['cur_route'] === 'memberVehiclesList') {
            $args['id'] = $this->view->getEnvironment()->getGlobals()['cur_subroute'];
        }

        $href = $this->routeparser->urlFor(
            $this->view->getEnvironment()->getGlobals()['cur_route'],
            $args
        );
        return $href;
    }
}
