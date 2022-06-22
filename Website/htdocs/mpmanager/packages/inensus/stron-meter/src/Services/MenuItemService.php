<?php

namespace Inensus\StronMeter\Services;


class MenuItemService
{

    public function createMenuItems()
    {

        $menuItem = [
            'name' => 'Stron Meter',
            'url_slug' => '',
            'md_icon' => 'bolt'
        ];
        $subMenuItems = array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/stron-meters/stron-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem' => $menuItem,'subMenuItems' => $subMenuItems];
    }
}
