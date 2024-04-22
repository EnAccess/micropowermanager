<?php

namespace Inensus\AirtelPaymentProvider\Services;


class MenuItemService
{
    const MENU_ITEM = 'Airtel';
    public function createMenuItems()
    {
        $menuItem = [
            'name' => self::MENU_ITEM,
            'url_slug' =>'',
            'md_icon' => 'money',
            'usage_type' => 'general',
        ];
        $subMenuItems= array();

        $subMenuItem1=[
            'name' => 'Overview',
            'url_slug' => '/airtel-payment/airtel-payment-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem'=>$menuItem,'subMenuItems'=>$subMenuItems];


    }
}