<?php

namespace Inensus\SunKingMeter\Services;


class MenuItemService
{
    const MENU_ITEM = 'SunKing Meter';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'SunKing Meter',
            'url_slug' => '',
            'md_icon' => 'bolt'
        ];
        $subMenuItems = array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/sun-king-meters/sun-king-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];

    }
}