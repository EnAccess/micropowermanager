<?php

namespace Inensus\CalinSmartMeter\Services;


class MenuItemService
{
    const MENU_ITEM = 'CalinSmart Meter';

    public function createMenuItems()
    {

        $menuItem = [
            'name' => 'CalinSmart Meter',
            'url_slug' => '',
            'md_icon' => 'bolt'
        ];
        $subMenuItems = array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/calin-smart-meters/calin-smart-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem' => $menuItem,'subMenuItems' => $subMenuItems];
    }
}
