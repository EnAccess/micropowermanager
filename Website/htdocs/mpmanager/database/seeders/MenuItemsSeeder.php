<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuItemsSeeder extends Seeder
{
    public static $MENU_ITEMS = [
        [
            'name' => 'Dashboard',
            'url_slug' => '/',
            'md_icon' => 'home',
            'menu_order' => '1',
            'usage_type' => 'general',
            'route_data' => '[{"path":"/","component":"pages/Dashboard/index.vue","name":"cluster-list-dashboard","meta":{"layout":"default","breadcrumb":{"level":"base","name":"Clusters","link":"/"}}},{"path":"/dashboards/mini-grid/","component":"pages/Dashboard/MiniGrid/index.vue","meta":{"layout":"default","breadcrumb":{"level":"base","name":"Mini-Grids","link":"/dashboards/mini-grid"}}},{"path":"/clusters","component":"pages/Dashboard/index.vue","name":"cluster-list","meta":{"layout":"default","breadcrumb":{"level":"base","name":"Clusters","link":"/clusters"}}}]',
        ],
        [
            'name' => 'Customers',
            'url_slug' => '/people/page/1',
            'md_icon' => 'supervisor_account',
            'menu_order' => '2',
            'usage_type' => 'general',
            'route_data' => '[{"path":"/people","component":"pages/Client/index.vue","meta":{"layout":"default","breadcrumb":{"level":"base","name":"Customers","link":"/people"}}}]',
        ],
        [
            'name' => 'Agents',
            'url_slug' => '',
            'md_icon' => 'support_agent',
            'menu_order' => '3',
            'usage_type' => 'general',
            'route_data' => '[{"path":"/agents","component":"pages/Agent/index.vue","meta":{"layout":"default"}},{"path":"/commissions","component":"pages/Agent/Commission/index.vue","meta":{"layout":"default"}}]',
        ],
        [
            'name' => 'Meters',
            'url_slug' => '',
            'md_icon' => 'bolt',
            'menu_order' => '4',
            'usage_type' => 'mini-grid',
            'route_data' => '[{"path":"/meters","component":"pages/Meter/index.vue","meta":{"layout":"default","breadcrumb":{"level":"base","name":"Meters","link":"/meters"}}},{"path":"/meters/types","component":"pages/MeterType/index.vue","meta":{"layout":"default"}}]',
        ],
        [
            'name' => 'Transactions',
            'url_slug' => '/transactions/page/1',
            'md_icon' => 'account_balance',
            'menu_order' => '5',
            'usage_type' => 'general',
            'route_data' => '[{"path":"/transactions","component":"pages/Transaction/index.vue","meta":{"layout":"default","breadcrumb":{"level":"base","name":"Transactions","link":"/transactions"}}},{"path":"/transactions/search","component":"pages/Transaction/index.vue","meta":{"layout":"default"}}]',
        ],
        [
            'name' => 'Tickets',
            'url_slug' => '',
            'md_icon' => 'confirmation_number',
            'menu_order' => '6',
            'usage_type' => 'general',
            'route_data' => '[{"path":"/tickets","component":"pages/Ticket/index.vue","meta":{"layout":"default"}},{"path":"/tickets/settings/users","component":"pages/Ticket/Setting/User/index.vue","meta":{"layout":"default"}},{"path":"/tickets/settings/categories","component":"pages/Ticket/Setting/Category/index.vue","meta":{"layout":"default"}}]',
        ],
        [
            'name' => 'Tariffs',
            'url_slug' => '/tariffs',
            'md_icon' => 'widgets',
            'menu_order' => '7',
            'usage_type' => 'mini-grid',
            'route_data' => '[{"path":"/tariffs","component":"pages/Tariff/index.vue","meta":{"layout":"default","breadcrumb":{"level":"base","name":"Tariffs","link":"/tariffs"}}}]',
        ],
        [
            'name' => 'Targets',
            'url_slug' => '/targets',
            'md_icon' => 'gps_fixed',
            'menu_order' => '8',
            'usage_type' => 'mini-grid',
            'route_data' => '[{"path":"/targets","component":"pages/Target/index.vue","name":"target-list","meta":{"layout":"default"}}]',
        ],
        [
            'name' => 'Reports',
            'url_slug' => '/reports',
            'md_icon' => 'text_snippet',
            'menu_order' => '9',
            'usage_type' => 'general',
            'route_data' => '[{"path":"/reports","component":"pages/Report/index.vue","meta":{"layout":"default"}}]',
        ],
        [
            'name' => 'Messages',
            'url_slug' => '',
            'md_icon' => 'sms',
            'menu_order' => '10',
            'usage_type' => 'general',
            'route_data' => '[{"path":"/sms/list","component":"pages/Sms/index.vue","name":"sms-list","meta":{"layout":"default"}},{"path":"/sms/newsms","component":"pages/Sms/New/index.vue","name":"new-sms","meta":{"layout":"default"}}]',
        ],
        [
            'name' => 'Appliances',
            'url_slug' => '/assets/page/1',
            'md_icon' => 'devices_other',
            'menu_order' => '11',
            'usage_type' => 'general',
            'route_data' => '[{"path":"/assets","component":"pages/Appliance/index.vue","name":"asset","meta":{"layout":"default"}}]',
        ],
        [
            'name' => 'Maintenance',
            'url_slug' => '/maintenance',
            'md_icon' => 'home_repair_service',
            'menu_order' => '12',
            'usage_type' => 'mini-grid',
            'route_data' => '[{"path":"/maintenance","component":"pages/Maintenance/index.vue","name":"maintenance","meta":{"layout":"default"}}]',
        ],
        [
            'name' => 'Solar Home Systems',
            'url_slug' => '/solar-home-systems/page/1',
            'md_icon' => 'solar_power',
            'menu_order' => '13',
            'usage_type' => 'shs',
            'route_data' => '[{"path":"/solar-home-systems","component":"pages/SolarHomeSystem/index.vue","meta":{"layout":"default"}}]',
        ],
        [
            'name' => 'E-Bikes',
            'url_slug' => '/e-bikes/page/1',
            'md_icon' => 'electric_bike',
            'menu_order' => '14',
            'usage_type' => 'e-bike',
            'route_data' => '[{"path":"/e-bikes","component":"pages/EBikes/index.vue","meta":{"layout":"default"}}]',
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::connection('shard')->table('menu_items')->insert(self::$MENU_ITEMS
        );
    }
}
