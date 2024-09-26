<?php

namespace Inensus\CalinMeter\Services;

class MenuItemService
{
    public const MENU_ITEM = 'Calin Meter';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'Calin Meter',
            'url_slug' => '',
            'md_icon' => 'bolt',
            'usage_type' => 'mini-grid',
        ];
        $subMenuItems = [];

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/calin-meters/calin-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];
    }
}
