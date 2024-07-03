<?php

namespace Inensus\SparkMeter\Services;



class MenuItemService
{
    const MENU_ITEM = 'Spark Meter';
    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'Spark Meter',
            'url_slug' => '',
            'md_icon' => 'bolt',
            'usage_type' => 'mini-grid',
            'route_data' => json_encode([
                [
                    'path' => '/spark-meters/sm-overview',
                    'component' => 'plugins/spark-meter/js/modules/Overview/Overview.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ],
                [
                    'path' => '/spark-meters/sm-site',
                    'component' => 'plugins/spark-meter/js/modules/Site/SiteList.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ],
                [
                    'path' => '/spark-meters/sm-meter-model',
                    'component' => 'plugins/spark-meter/js/modules/MeterModel/MeterModelList.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ],
                [
                    'path' => '/spark-meters/sm-customer',
                    'component' => 'plugins/spark-meter/js/modules/Customer/CustomerList.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ],
                [
                    'path' => '/spark-meters/sm-tariff',
                    'component' => 'plugins/spark-meter/js/modules/Tariff/TariffList.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ],
                [
                    'path' => '/spark-meters/sm-sales-account',
                    'component' => 'plugins/spark-meter/js/modules/SalesAccount/SalesAccountList.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ],
                [
                    'path' => '/spark-meters/sm-setting',
                    'component' => 'plugins/spark-meter/js/modules/Setting/Setting.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ]
            ])
        ];

        $subMenuItems = array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/spark-meters/sm-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        $subMenuItem2 = [
            'name' => 'Sites',
            'url_slug' => '/spark-meters/sm-site/page/1',
        ];
        array_push($subMenuItems, $subMenuItem2);

        $subMenuItem3 = [
            'name' => 'Meter Models',
            'url_slug' => '/spark-meters/sm-meter-model/page/1',
        ];
        array_push($subMenuItems, $subMenuItem3);

        $subMenuItem4 = [
            'name' => 'Tariffs',
            'url_slug' => '/spark-meters/sm-tariff/page/1',
        ];
        array_push($subMenuItems, $subMenuItem4);

        $subMenuItem5 = [
            'name' => 'Customers',
            'url_slug' => '/spark-meters/sm-customer/page/1',
        ];
        array_push($subMenuItems, $subMenuItem5);
        $subMenuItem6 = [
            'name' => 'Sales Accounts',
            'url_slug' => '/spark-meters/sm-sales-account/page/1',
        ];
        array_push($subMenuItems, $subMenuItem6);
        $subMenuItem7 = [
            'name' => 'Settings',
            'url_slug' => '/spark-meters/sm-setting',
        ];
        array_push($subMenuItems, $subMenuItem7);
        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];
    }
}
