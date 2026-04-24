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

use DI\Attribute\Inject;
use Galette\Core\Db;
use Galette\Core\Login;
use Galette\Core\Plugins\DashboardProviderInterface;
use Galette\Core\Plugins\InstallableInterface;
use Galette\Core\Plugins\MemberActionProviderInterface;
use Galette\Core\Plugins\MenuProviderInterface;
use Galette\Entity\Adherent;
use Galette\Core\GalettePlugin;

/**
 * Galette Auto plugin main class
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */

class PluginGaletteAuto extends GalettePlugin implements MenuProviderInterface, DashboardProviderInterface, MemberActionProviderInterface, InstallableInterface
{
    #[Inject]
    private readonly Db $zdb; //@phpstan-ignore property.uninitializedReadonly,property.onlyRead (injected from DI)

    /**
     * Get plugins menus
     *
     * @return array<string, string|array<string,mixed>>
     */
    public function getMenus(): array
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
     * Get plugins public menus
     *
     * @return array<int, string|array<string,mixed>>
     */
    public function getPublicMenus(): array
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
     * Get current logged-in user plugins dashboards
     *
     * @return array<int, string|array<string,mixed>>
     */
    public function getMyDashboards(): array
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
     * Get plugins dashboards
     *
     * @return array<int, string|array<string,mixed>>
     */
    public function getDashboards(): array
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
    public function getListActions(Adherent $member): array
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
    public function getDetailedActions(Adherent $member): array
    {
        return $this->getListActions($member);
    }

    /**
     * Get batch actions contents
     *
     * @return array<int, string|array<string,mixed>>
     */
    public function getBatchActions(): array
    {
        return [];
    }

    /**
     * Is the plugin fully installed (including database, extra configuration, etc.)?
     */
    public function isInstalled(): bool
    {
        return
            $this->zdb->tableExists(AUTO_PREFIX . Auto::TABLE)
            && $this->zdb->tableExists(AUTO_PREFIX . Body::TABLE)
            && $this->zdb->tableExists(AUTO_PREFIX . Brand::TABLE)
            && $this->zdb->tableExists(AUTO_PREFIX . Color::TABLE)
            && $this->zdb->tableExists(AUTO_PREFIX . Finition::TABLE)
            && $this->zdb->tableExists(AUTO_PREFIX . Model::TABLE)
            && $this->zdb->tableExists(AUTO_PREFIX . State::TABLE)
            && $this->zdb->tableExists(AUTO_PREFIX . Transmission::TABLE)
        ;
    }
}
