<?php

namespace Inensus\SteamaMeter\Services;

use App\Models\MenuItems;

class MenuItemService
{
    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'Steamaco Meter',
            'url_slug' => '',
            'md_icon' => 'bolt'
        ];
        $subMenuItems = array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/steama-meters/steama-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        $subMenuItem2 = [
            'name' => 'Sites',
            'url_slug' => '/steama-meters/steama-site/page/1',
        ];
        array_push($subMenuItems, $subMenuItem2);

        $subMenuItem3 = [
            'name' => 'Customers',
            'url_slug' => '/steama-meters/steama-customer/page/1',
        ];
        array_push($subMenuItems, $subMenuItem3);

        $subMenuItem4 = [
            'name' => 'Meters',
            'url_slug' => '/steama-meters/steama-meter/page/1',
        ];
        array_push($subMenuItems, $subMenuItem4);

        $subMenuItem5 = [
            'name' => 'Agents',
            'url_slug' => '/steama-meters/steama-agent/page/1',
        ];
        array_push($subMenuItems, $subMenuItem5);

        $subMenuItem6 = [
            'name' => 'Settings',
            'url_slug' => '/steama-meters/steama-setting',
        ];
        array_push($subMenuItems, $subMenuItem6);

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];
    }
}
