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
            'md_icon' =>'upload_file',
            'usage_type' =>'general',
            'route_data' => json_encode([
                'path' => '/wavecom/transactions',
                'component' => "plugins/wavecom-payment-provider/js/modules/Component.vue",
                'meta' => [
                    'layout' => 'default',
                ],
            ])

        ];
        return ['menuItem'=>$menuItem,'subMenuItems'=>[]];
    }
}
