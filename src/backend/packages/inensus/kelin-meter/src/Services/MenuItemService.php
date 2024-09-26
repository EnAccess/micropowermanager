<?php

namespace Inensus\KelinMeter\Services;

class MenuItemService
{
    public const MENU_ITEM = 'Kelin Meter';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'Kelin Meter',
            'url_slug' => '',
            'md_icon' => 'bolt',
            'usage_type' => 'mini-grid',
        ];
        $subMenuItems = [];

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/kelin-meters/kelin-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        $subMenuItem3 = [
            'name' => 'Customers',
            'url_slug' => '/kelin-meters/kelin-customer/page/1',
        ];
        array_push($subMenuItems, $subMenuItem3);

        $subMenuItem4 = [
            'name' => 'Meters',
            'url_slug' => '/kelin-meters/kelin-meter/page/1',
        ];
        array_push($subMenuItems, $subMenuItem4);

        $subMenuItem4 = [
            'name' => 'Settings',
            'url_slug' => '/kelin-meters/kelin-setting',
        ];
        array_push($subMenuItems, $subMenuItem4);

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];
    }
}
