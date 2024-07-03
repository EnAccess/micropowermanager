<?php

namespace Inensus\AngazaSHS\Services;


class MenuItemService
{
    const MENU_ITEM = 'Angaza SHS';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'Angaza SHS',
            'url_slug' => '',
            'md_icon' => 'bolt',
            'usage_type' => 'shs',
            'route_data' => json_encode([
                'path' => '/angaza-shs/angaza-overview',
                'component' => 'plugins/angaza-shs/js/modules/Overview/Overview.vue',
                'meta' => [
                    'layout' => 'default'
                ]
            ])

        ];
        $subMenuItems = array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/angaza-shs/angaza-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];

    }
}