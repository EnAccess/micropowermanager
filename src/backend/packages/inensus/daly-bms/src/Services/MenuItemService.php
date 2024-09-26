<?php

namespace Inensus\DalyBms\Services;

class MenuItemService
{
    public const MENU_ITEM = 'DalyBms';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'Daly Bms',
            'url_slug' => '',
            'md_icon' => 'charging_station',
            'usage_type' => 'e-bike',
        ];
        $subMenuItems = [];

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/daly-bms/daly-bms-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];
    }
}
