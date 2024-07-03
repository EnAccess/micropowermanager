<?php

namespace Inensus\MicroStarMeter\Services;


class MenuItemService
{
    const MENU_ITEM = 'MicroStar Meter';

    public function createMenuItems()
    {
        $menuItem = [
            'name' =>'MicroStar Meter',
            'url_slug' =>'',
            'md_icon' =>'bolt',
            'usage_type' =>'mini-grid',
            'route_data' => json_encode([
                'path' => '/micro-star-meters/micro-star-overview',
                'component' => 'plugins/micro-star-meter/js/modules/Overview/Overview.vue',
                'meta' => [
                    'layout' => 'default',
                ],
            ])

        ];
        $subMenuItems= array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/micro-star-meters/micro-star-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem'=>$menuItem,'subMenuItems'=>$subMenuItems];

    }
}