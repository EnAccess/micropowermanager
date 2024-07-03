<?php

namespace Inensus\KelinMeter\Services;


class MenuItemService
{
    const MENU_ITEM = 'Kelin Meter';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'Kelin Meter',
            'url_slug' => '',
            'md_icon' => 'bolt',
            'usage_type' => 'mini-grid',
            'route_data' => json_encode([
                [
                    'path' => '/kelin-meters/kelin-overview',
                    'component' => 'plugins/kelin-meter/js/modules/Overview/Overview.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ],
                [
                    'path' => '/kelin-meters/kelin-customer',
                    'component' => 'plugins/kelin-meter/js/modules/Customer/List.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ],
                [
                    'path' => '/kelin-meters/kelin-meter',
                    'component' => 'plugins/kelin-meter/js/modules/Meter/List.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ],
                [
                    'path' => '/kelin-meters/kelin-setting',
                    'component' => 'plugins/kelin-meter/js/modules/Setting/Setting.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ]
            ])

        ];
        $subMenuItems= array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/kelin-meters/kelin-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        $subMenuItem3 = [
            'name' => 'Customers',
            'url_slug' => '/kelin-meters/kelin-customer/page/1',
        ];
        array_push($subMenuItems, $subMenuItem3);

        $subMenuItem4 = [
            'name' => 'Meters',
            'url_slug' => '/kelin-meters/kelin-meter/page/1',
        ];
        array_push($subMenuItems, $subMenuItem4);

        $subMenuItem4 = [
            'name' => 'Settings',
            'url_slug' => '/kelin-meters/kelin-setting',
        ];
        array_push($subMenuItems, $subMenuItem4);
        return ['menuItem'=>$menuItem,'subMenuItems'=>$subMenuItems];


    }
}