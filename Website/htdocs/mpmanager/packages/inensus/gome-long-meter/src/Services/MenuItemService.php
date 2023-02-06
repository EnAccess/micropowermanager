<?php

namespace Inensus\GomeLongMeter\Services;


class MenuItemService
{
    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'GomeLong Meter',
            'url_slug' => '',
            'md_icon' => 'bolt'
        ];
        $subMenuItems = array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/gome-long-meters/gome-long-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];


    }
}