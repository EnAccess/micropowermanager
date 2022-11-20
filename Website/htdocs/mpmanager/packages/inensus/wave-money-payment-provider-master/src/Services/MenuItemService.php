<?php

namespace Inensus\WaveMoneyPaymentProvider\Services;


class MenuItemService
{
    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'Wave Money',
            'url_slug' => '',
            'md_icon' => 'money'
        ];
        $subMenuItems= array();

        $subMenuItem1=[
            'name' => 'Overview',
            'url_slug' => '/wave-money/wave-money-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem'=>$menuItem,'subMenuItems'=>$subMenuItems];


    }
}