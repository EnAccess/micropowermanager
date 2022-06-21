<?php

namespace Inensus\CalinMeter\Services;


use App\Models\MenuItems;

class MenuItemService
{
    public function createMenuItems()
    {
        $menuItem = [
            'name' =>'Calin Meter',
            'url_slug' =>'',
            'md_icon' =>'bolt'
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