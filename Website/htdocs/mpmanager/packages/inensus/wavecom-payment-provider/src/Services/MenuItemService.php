<?php

namespace Inensus\WavecomPaymentProvider\Services;

class MenuItemService
{
    public const MENU_ITEM = 'Wavecom Payment Provider';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'Wavecom Payment Provider',
            'url_slug' => '/wavecom/transactions',
            'md_icon' => 'upload_file',
            'usage_type' => 'general',
        ];

        return ['menuItem' => $menuItem, 'subMenuItems' => []];
    }
}
