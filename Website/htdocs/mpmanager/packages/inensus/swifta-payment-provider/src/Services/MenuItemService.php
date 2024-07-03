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
            'md_icon' => 'money',
            'usage_type' => 'general',
            'route_data' => json_encode([
                'path' => '/swifta-payment/swifta-payment-overview',
                'component' => 'plugins/swifta-payment-provider/js/modules/Overview/Overview.vue',
                'meta' => [
                    'layout' => 'default',
                ],
            ])

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