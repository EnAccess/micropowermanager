<?php

namespace Inensus\GomeLongMeter\Services;


class MenuItemService
{
    const MENU_ITEM = 'GomeLong Meter';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'GomeLong Meter',
            'url_slug' => '',
            'md_icon' => 'bolt',
            'usage_type' => 'mini-grid',
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