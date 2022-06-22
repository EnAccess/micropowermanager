<?php

namespace Inensus\CalinSmartMeter\Services;


class MenuItemService
{

    public function createMenuItems()
    {

        $menuItem = [
            'name' => 'Calin Smart Meter',
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
