<?php

namespace Inensus\CalinSmartMeter\Services;


class MenuItemService
{
    const MENU_ITEM = 'CalinSmart Meter';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'CalinSmart Meter',
            'url_slug' => '',
            'md_icon' => 'bolt',
            'usage_type' => 'mini-grid',
            'route_data' => json_encode([
                'path' => '/calin-smart-meters/calin-smart-overview',
                'component' => 'plugins/calin-smart-meter/js/modules/Overview/Overview.vue',

                'meta' => [
                    'layout' => 'default'
                ]
            ])

        ];
        $subMenuItems = array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/calin-smart-meters/calin-smart-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem' => $menuItem,'subMenuItems' => $subMenuItems];
    }
}
