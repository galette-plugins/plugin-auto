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

namespace GaletteAuto;

use Galette\Core\Login;
use Galette\Entity\Adherent;
use Galette\Core\GalettePlugin;

/**
 * Galette Auto plugin main class
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */

class PluginGaletteAuto extends GalettePlugin
{
    /**
     * Extra menus entries
     *
     * @return array<string, string|array<string,mixed>>
     */
    public static function getMenusContents(): array
    {
        /** @var Login $login */
        global $login;
        $menus = [];

        if ($login->isLogged()) {
            if ($login->isAdmin() || $login->isStaff() || $login->isGroupManager()) {
                $menus['plugin_auto'] = [
                    'title' => _T("Cars", "auto"),
                    'icon' => 'car',
                    'items' => []
                ];
            }

            if ($login->isAdmin() || $login->isStaff()) {
                $menus['plugin_auto']['items'] = array_merge(
                    $menus['plugin_auto']['items'],
                    [
                        [
                            'label' => _T("Colors list", "auto"),
                            'route' => ['name' => 'colorsList']
                        ],
                        [
                            'label' => _T("States list", "auto"),
                            'route' => ['name' => 'statesList']
                        ],
                        [
                            'label' => _T("Finitions list", "auto"),
                            'route' => ['name' => 'finitionsList']
                        ],
                        [
                            'label' => _T("Bodies list", "auto"),
                            'route' => ['name' => 'bodiesList']
                        ],
                        [
                            'label' => _T("Transmissions list", "auto"),
                            'route' => ['name' => 'transmissionsList']
                        ],
                        [
                            'label' => _T("Brands list", "auto"),
                            'route' => ['name' => 'brandsList']
                        ],
                        [
                            'label' => _T("Models list", "auto"),
                            'route' => [
                                'name' => 'modelsList',
                                'aliases' => ['modelAdd', 'modelEdit']
                            ]
                        ],

                    ]
                );
            }

            if ($login->isAdmin() || $login->isStaff() || $login->isGroupManager()) {
                $menus['plugin_auto']['items'][] = [
                    'label' => _T("Cars list", "auto"),
                    'route' => [
                        'name' => 'vehiclesList',
                        'aliases' => ['vehicleAdd', 'vehicleEdit']
                    ]
                ];
            }

            // Super Admin is not a regular user
            if (!$login->isSuperAdmin()) {
                $menus['myaccount'] = [
                    'items' => [
                        [
                            'label' => _T("My cars", "auto"),
                            'route' => ['name' => 'myVehiclesList']
                        ]
                    ]
                ];
            }
        }

        return $menus;
    }

    /**
     * Extra public menus entries
     *
     * @return array<int, string|array<string,mixed>>
     */
    public static function getPublicMenusItemsList(): array
    {
        return [
            [
                'label' => _T("Vehicles", "auto"),
                'route' => [
                    'name' => 'publicVehiclesList'
                ],
                'icon' => 'car'
            ]
        ];
    }

    /**
     * Get current logged-in user dashboards contents
     *
     * @return array<int, string|array<string,mixed>>
     */
    public static function getMyDashboardsContents(): array
    {
        /** @var Login $login */
        global $login;

        if ($login->isSuperAdmin()) {
            return [];
        }

        return [
            [
                'label' => _T("My cars", "auto"),
                'route' => [
                    'name' => 'myVehiclesList'
                ],
                'icon' => 'oncoming_automobile'
            ]
        ];
    }

    /**
     * Get dashboards contents
     *
     * @return array<int, string|array<string,mixed>>
     */
    public static function getDashboardsContents(): array
    {
        return [];
    }

    /**
     * Get actions contents
     *
     * @param Adherent $member Member instance
     *
     * @return array<int, string|array<string,mixed>>
     */
    public static function getListActionsContents(Adherent $member): array
    {
        return [
            [
                'label' => _T("Member's cars", "auto"),
                'route' => [
                    'name' => 'memberVehiclesList',
                    'args' => ['id' => $member->id]
                ],
                'icon' => 'truck pickup grey'
            ],
        ];
    }

    /**
     * Get detailed actions contents
     *
     * @param Adherent $member Member instance
     *
     * @return array<int, string|array<string,mixed>>
     */
    public static function getDetailedActionsContents(Adherent $member): array
    {
        return static::getListActionsContents($member);
    }

    /**
     * Get batch actions contents
     *
     * @return array<int, string|array<string,mixed>>
     */
    public static function getBatchActionsContents(): array
    {
        return [];
    }
}
