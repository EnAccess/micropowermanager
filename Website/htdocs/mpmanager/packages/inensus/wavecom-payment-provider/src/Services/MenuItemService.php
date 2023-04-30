<?php

namespace Inensus\WavecomPaymentProvider\Services;

class MenuItemService
{

    const MENU_ITEM = 'Wavecom Payment Provider';
    public function createMenuItems()
    {
        $menuItem = [
            'name' =>'Wavecom Payment Provider',
            'url_slug' =>'/wavecom/transactions',
            'md_icon' =>'upload_file'
        ];
        return ['menuItem'=>$menuItem,'subMenuItems'=>[]];
    }
}
