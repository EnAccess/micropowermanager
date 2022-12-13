<?php

namespace Inensus\SwiftaPaymentProvider\Services;

class MenuItemService
{
    const MENU_ITEM = 'Swifta';
    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'Swifta',
            'url_slug' => '',
            'md_icon' => 'money'
        ];
        $subMenuItems= array();

        $subMenuItem1=[
            'name' => 'Overview',
            'url_slug' => '/swifta-payment/swifta-payment-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem'=>$menuItem,'subMenuItems'=>$subMenuItems];


    }
}