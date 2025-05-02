<?php

namespace Inensus\ChintMeter\Services;

class MenuItemService {
    public const MENU_ITEM = 'Chint Meter';

    public function createMenuItems() {
        $menuItem = [
            'name' => 'Chint Meter',
            'url_slug' => '',
            'md_icon' => 'bolt',
        ];
        $subMenuItems = [];

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/chint-meters/chint-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];
    }
}
