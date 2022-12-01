<?php

namespace Inensus\MicroStarMeter\Services;


class MenuItemService
{
    public function createMenuItems()
    {
        $menuItem = [
            'name' =>'MicroStar Meter',
            'url_slug' =>'',
            'md_icon' =>'bolt'
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