<?php

namespace Inensus\CalinMeter\Services;


use App\Models\MenuItems;

class MenuItemService
{
    const MENU_ITEM = 'Calin Meter';

    public function createMenuItems()
    {
        $menuItem = [
            'name' =>'Calin Meter',
            'url_slug' =>'',
            'md_icon' =>'bolt',
            'usage_type' =>'mini-grid',
            'route_data' => json_encode([
                'path' => '/calin-meters/calin-overview',
                'component' => 'plugins/calin-meter/js/modules/Overview/Overview.vue',

                'meta' => [
                    'layout' => 'default'
                ]
            ])

        ];
        $subMenuItems= array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/calin-meters/calin-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem'=>$menuItem,'subMenuItems'=>$subMenuItems];

    }
}