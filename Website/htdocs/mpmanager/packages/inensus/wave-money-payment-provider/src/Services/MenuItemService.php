<?php

namespace Inensus\WaveMoneyPaymentProvider\Services;


class MenuItemService
{
    const MENU_ITEM = 'WaveMoney';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'WaveMoney',
            'url_slug' => '',
            'md_icon' => 'money',
            'usage_type' => 'general',
            'route_data' => json_encode([
                'path' => '/wave-money/wave-money-overview',
                'component' => "plugins/wave-money-payment-provider/js/modules/Overview/Overview.vue",
                'meta' => [
                    'layout' => 'default',
                ],
            ])

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