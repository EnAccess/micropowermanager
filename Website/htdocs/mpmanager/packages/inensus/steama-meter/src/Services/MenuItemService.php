<?php

namespace Inensus\SteamaMeter\Services;

use App\Models\MenuItems;

class MenuItemService
{
    const MENU_ITEM = 'Steamaco Meter';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'Steamaco Meter',
            'url_slug' => '',
            'md_icon' => 'bolt',
            'usage_type' => 'mini-grid',
            'route_data' => json_encode([
                [
                    'path' => '/steama-meters/steama-overview',
                    'component' => 'plugins/steama-meter/js/modules/Overview/Overview.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ],
                [
                    'path' => '/steama-meters/steama-site',
                    'component' => 'plugins/steama-meter/js/modules/Site/SiteList.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ],
                [
                    'path' => '/steama-meters/steama-customer',
                    'component' => 'plugins/steama-meter/js/modules/Customer/CustomerList.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ],
                [
                    'path' => '/steama-meters/steama-meter',
                    'component' => 'plugins/steama-meter/js/modules/Meter/MeterList.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ],
                [
                    'path' => '/steama-meters/steama-agent',
                    'component' => 'plugins/steama-meter/js/modules/Agent/AgentList.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ],
                [
                    'path' => '/steama-meters/steama-setting',
                    'component' => 'plugins/steama-meter/js/modules/Setting/Setting.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ]
            ])
        ];

        $subMenuItems = array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/steama-meters/steama-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        $subMenuItem2 = [
            'name' => 'Sites',
            'url_slug' => '/steama-meters/steama-site/page/1',
        ];
        array_push($subMenuItems, $subMenuItem2);

        $subMenuItem3 = [
            'name' => 'Customers',
            'url_slug' => '/steama-meters/steama-customer/page/1',
        ];
        array_push($subMenuItems, $subMenuItem3);

        $subMenuItem4 = [
            'name' => 'Meters',
            'url_slug' => '/steama-meters/steama-meter/page/1',
        ];
        array_push($subMenuItems, $subMenuItem4);

        $subMenuItem5 = [
            'name' => 'Agents',
            'url_slug' => '/steama-meters/steama-agent/page/1',
        ];
        array_push($subMenuItems, $subMenuItem5);

        $subMenuItem6 = [
            'name' => 'Settings',
            'url_slug' => '/steama-meters/steama-setting',
        ];
        array_push($subMenuItems, $subMenuItem6);

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];
    }
}
