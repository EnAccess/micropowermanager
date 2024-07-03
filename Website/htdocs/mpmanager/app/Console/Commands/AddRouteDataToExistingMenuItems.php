<?php

namespace App\Console\Commands;

use App\Services\MenuItemsService;
use Database\Seeders\MenuItemsSeeder;

class AddRouteDataToExistingMenuItems extends AbstractSharedCommand
{
    protected $signature = 'route-data-to-menu-items:add';
    protected $description = 'adds route_data to menu_items table for existing companies';
    public function __construct(private MenuItemsService $menuItemService)
    {
        parent::__construct();
    }
    public function handle(): void
    {
        $coreMenuItems = MenuItemsSeeder::$MENU_ITEMS;
        $pluginMenuItems = [
            [
                'name' => 'Angaza SHS',
                'url_slug' => '',
                'md_icon' => 'bolt',
                'usage_type' => 'shs',
                'route_data' => json_encode([
                    'path' => '/angaza-shs/angaza-overview',
                    'component' => 'plugins/angaza-shs/js/modules/Overview/Overview.vue',
                    'meta' => [
                        'layout' => 'default'
                    ]
                ])

            ],
            [
                'name' => 'Bulk Registration',
                'url_slug' => '/bulk-registration/bulk-registration',
                'md_icon' => 'upload_file',
                'usage_type' => 'general',
                [
                    'path' => '/bulk-registration/bulk-registration',
                    'component' => 'plugins/bulk-registration/js/modules/Csv.vue',
                    'meta' => [
                        'layout' => 'default',
                    ],
                ]

            ],
            [
                'name' =>'Calin Meter',
                'url_slug' =>'',
                'md_icon' =>'bolt',
                'usage_type' =>'mini-grid',
                'route_data' => json_encode([
                    'path' => '/calin-meters/calin-overview',
                    'component' => 'plugins/calin-meter/js/modules/Overview/Overview.vue',

                    'meta' => [
                        'layout' => 'default'
                    ]
                ])

            ],
            [
                'name' => 'CalinSmart Meter',
                'url_slug' => '',
                'md_icon' => 'bolt',
                'usage_type' => 'mini-grid',
                'route_data' => json_encode([
                    'path' => '/calin-smart-meters/calin-smart-overview',
                    'component' => 'plugins/calin-smart-meter/js/modules/Overview/Overview.vue',

                    'meta' => [
                        'layout' => 'default'
                    ]
                ])

            ],
            [
                'name' => 'Daly Bms',
                'url_slug' => '',
                'md_icon' => 'charging_station',
                'usage_type' => 'e-bike',
                'route_data' => json_encode([
                    'path' => '/daly-bms/daly-bms-overview',
                    'component' => 'plugins/daly-bms/js/modules/Overview/Overview.vue',

                    'meta' => [
                        'layout' => 'default'
                    ]
                ])

            ],
            [
                'name' => 'GomeLong Meter',
                'url_slug' => '',
                'md_icon' => 'bolt',
                'usage_type' => 'mini-grid',
                'route_data' => json_encode([
                    'path' => '/gome-long-meters/gome-long-overview',
                    'component' => 'plugins/gome-long-meter/js/modules/Overview/Overview.vue',

                    'meta' => [
                        'layout' => 'default',
                    ],
                ])

            ],
            [
                'name' => 'Kelin Meter',
                'url_slug' => '',
                'md_icon' => 'bolt',
                'usage_type' => 'mini-grid',
                'route_data' => json_encode([
                    [
                        'path' => '/kelin-meters/kelin-overview',
                        'component' => 'plugins/kelin-meter/js/modules/Overview/Overview.vue',
                        'meta' => [
                            'layout' => 'default'
                        ]
                    ],
                    [
                        'path' => '/kelin-meters/kelin-customer',
                        'component' => 'plugins/kelin-meter/js/modules/Customer/List.vue',
                        'meta' => [
                            'layout' => 'default'
                        ]
                    ],
                    [
                        'path' => '/kelin-meters/kelin-meter',
                        'component' => 'plugins/kelin-meter/js/modules/Meter/List.vue',
                        'meta' => [
                            'layout' => 'default'
                        ]
                    ],
                    [
                        'path' => '/kelin-meters/kelin-setting',
                        'component' => 'plugins/kelin-meter/js/modules/Setting/Setting.vue',
                        'meta' => [
                            'layout' => 'default'
                        ]
                    ]
                ])

            ],
            [
                'name' =>'MicroStar Meter',
                'url_slug' =>'',
                'md_icon' =>'bolt',
                'usage_type' =>'mini-grid',
                'route_data' => json_encode([
                    'path' => '/micro-star-meters/micro-star-overview',
                    'component' => 'plugins/micro-star-meter/js/modules/Overview/Overview.vue',
                    'meta' => [
                        'layout' => 'default',
                    ],
                ])

            ],
            [
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
            ],
            [
                'name' => 'Steamaco Meter',
                'url_slug' => '',
                'md_icon' => 'bolt',
                'usage_type' => 'mini-grid',
                'route_data' => json_encode([
                    [
                        'path' => '/steama-meters/steama-overview',
                        'component' => 'plugins/steama-meter/js/modules/Overview/Overview.vue',
                        'meta' => [
                            'layout' => 'default'
                        ]
                    ],
                    [
                        'path' => '/steama-meters/steama-site',
                        'component' => 'plugins/steama-meter/js/modules/Site/SiteList.vue',
                        'meta' => [
                            'layout' => 'default'
                        ]
                    ],
                    [
                        'path' => '/steama-meters/steama-customer',
                        'component' => 'plugins/steama-meter/js/modules/Customer/CustomerList.vue',
                        'meta' => [
                            'layout' => 'default'
                        ]
                    ],
                    [
                        'path' => '/steama-meters/steama-meter',
                        'component' => 'plugins/steama-meter/js/modules/Meter/MeterList.vue',
                        'meta' => [
                            'layout' => 'default'
                        ]
                    ],
                    [
                        'path' => '/steama-meters/steama-agent',
                        'component' => 'plugins/steama-meter/js/modules/Agent/AgentList.vue',
                        'meta' => [
                            'layout' => 'default'
                        ]
                    ],
                    [
                        'path' => '/steama-meters/steama-setting',
                        'component' => 'plugins/steama-meter/js/modules/Setting/Setting.vue',
                        'meta' => [
                            'layout' => 'default'
                        ]
                    ]
                ])
            ],
            [
                'name' => 'SunKing SHS',
                'url_slug' => '',
                'md_icon' => 'bolt',
                'usage_type' => 'shs',
                'route_data' => json_encode([
                    'path' => '/sun-king-shs/sun-king-overview',
                    'component' => 'plugins/sun-king-shs/js/modules/Overview/Overview.vue',
                    'meta' => [
                        'layout' => 'default',
                    ],
                ])

            ],
            [
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

            ],
            [
                'name' => 'Viber Messaging',
                'url_slug' => '',
                'md_icon' => 'message',
                'usage_type' => 'general',
                'route_data' => json_encode([
                    'path' => '/viber-messaging/viber-overview',
                    'component' => "plugins/viber-messaging/js/modules/Overview/Overview.vue",
                    'meta' => [
                        'layout' => 'default',
                    ],
                ])
            ],
            [
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

            ],
            [
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

            ],
        ];
        $menuItems = array_merge($coreMenuItems, $pluginMenuItems);
        foreach ($menuItems as $menuItem) {
            $existingMenuItem = $this->menuItemService->getByName($menuItem['name']);
            if ($existingMenuItem) {
                $this->menuItemService->update($existingMenuItem,$menuItem);
            }
        }
    }
}
