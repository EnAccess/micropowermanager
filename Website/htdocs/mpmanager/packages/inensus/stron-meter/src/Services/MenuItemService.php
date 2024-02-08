<?php

namespace Inensus\StronMeter\Services;


class MenuItemService
{
    const MENU_ITEM = 'Stron Meter';

    public function createMenuItems()
    {

        $menuItem = [
            'name' => 'Stron Meter',
            'url_slug' => '',
            'md_icon' => 'bolt',
            'usage_type' => 'mini-grid',
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
