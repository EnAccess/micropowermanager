<?php

namespace Inensus\SunKingSHS\Services;


class MenuItemService
{
    const MENU_ITEM = 'SunKing SHS';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'SunKing SHS',
            'url_slug' => '',
            'md_icon' => 'bolt',
            'usage_type' => 'shs',
            'route_data' => json_encode([
                'path' => '/sun-king-shs/sun-king-overview',
                'component' => 'plugins/sun-king-shs/js/modules/Overview/Overview.vue',
                'meta' => [
                    'layout' => 'default',
                ],
            ])

        ];
        $subMenuItems = array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/sun-king-shs/sun-king-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];

    }
}