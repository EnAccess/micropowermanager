<?php

namespace Inensus\AngazaSHS\Services;


class MenuItemService
{
    const MENU_ITEM = 'Angaza SHS';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'Angaza SHS',
            'url_slug' => '',
            'md_icon' => 'bolt'
        ];
        $subMenuItems = array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/angaza-shs/angaza-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];

    }
}