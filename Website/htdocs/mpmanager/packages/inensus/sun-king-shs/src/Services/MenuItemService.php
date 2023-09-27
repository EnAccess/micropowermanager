<?php

namespace Inensus\SunKingSHS\Services;


class MenuItemService
{
    const MENU_ITEM = 'SunKing SHS';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'SunKing SHS',
            'url_slug' => '',
            'md_icon' => 'bolt'
        ];
        $subMenuItems = array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/sun-king-shs/sun-king-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];

    }
}