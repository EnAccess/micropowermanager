<?php

namespace Inensus\WaveMoneyPaymentProvider\Services;

class MenuItemService
{
    public const MENU_ITEM = 'WaveMoney';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'WaveMoney',
            'url_slug' => '',
            'md_icon' => 'money',
            'usage_type' => 'general',
        ];
        $subMenuItems = [];

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/wave-money/wave-money-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];
    }
}
