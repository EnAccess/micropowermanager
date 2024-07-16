<?php

namespace Inensus\MicroStarMeter\Services;

class MenuItemService
{
    public const MENU_ITEM = 'MicroStar Meter';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'MicroStar Meter',
            'url_slug' => '',
            'md_icon' => 'bolt',
            'usage_type' => 'mini-grid',
        ];
        $subMenuItems = [];

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/micro-star-meters/micro-star-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];
    }
}
