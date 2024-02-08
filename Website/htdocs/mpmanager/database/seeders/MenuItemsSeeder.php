<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::connection('shard')->table('menu_items')->insert(array(
                [
                    'name' => 'Dashboard',
                    'url_slug' => '/',
                    'md_icon' => 'home',
                    'menu_order' => '1',
                    'usage_type' => 'general',
                ],
                [
                    'name' => 'Customers',
                    'url_slug' => '/people/page/1',
                    'md_icon' => 'supervisor_account',
                    'menu_order' => '2',
                    'usage_type' => 'general',
                ],
                [
                    'name' => 'Agents',
                    'url_slug' => '',
                    'md_icon' => 'support_agent',
                    'menu_order' => '3',
                    'usage_type' => 'general',
                ],
                [
                    'name' => 'Meters',
                    'url_slug' => '',
                    'md_icon' => 'bolt',
                    'menu_order' => '4',
                    'usage_type' => 'mini-grid',
                ],
                [
                    'name' => 'Transactions',
                    'url_slug' => '/transactions/page/1',
                    'md_icon' => 'account_balance',
                    'menu_order' => '5',
                    'usage_type' => 'general',
                ],
                [
                    'name' => 'Tickets',
                    'url_slug' => '',
                    'md_icon' => 'confirmation_number',
                    'menu_order' => '6',
                    'usage_type' => 'general',
                ],
                [
                    'name' => 'Tariffs',
                    'url_slug' => '/tariffs',
                    'md_icon' => 'widgets',
                    'menu_order' => '7',
                    'usage_type' => 'mini-grid',
                ],
                [
                    'name' => 'Targets',
                    'url_slug' => '/targets',
                    'md_icon' => 'gps_fixed',
                    'menu_order' => '8',
                    'usage_type' => 'mini-grid',
                ],
                [
                    'name' => 'Reports',
                    'url_slug' => '/reports',
                    'md_icon' => 'text_snippet',
                    'menu_order' => '9',
                    'usage_type' => 'general',
                ],
                [
                    'name' => 'Messages',
                    'url_slug' => '',
                    'md_icon' => 'sms',
                    'menu_order' => '10',
                    'usage_type' => 'general',
                ],
                [
                    'name' => 'Appliances',
                    'url_slug' => '/assets/page/1',
                    'md_icon' => 'devices_other',
                    'menu_order' => '11',
                    'usage_type' => 'general',
                ],
                [
                    'name' => 'Maintenance',
                    'url_slug' => '/maintenance',
                    'md_icon' => 'home_repair_service',
                    'menu_order' => '12',
                    'usage_type' => 'mini-grid',
                ],
                [
                    'name' => 'Solar Home Systems',
                    'url_slug' => '/solar-home-systems/page/1',
                    'md_icon' => 'solar_power',
                    'menu_order' => '13',
                    'usage_type' => 'shs',
                ],
                [
                    'name' => 'E-Bikes',
                    'url_slug' => '/e-bikes/page/1',
                    'md_icon' => 'electric_bike',
                    'menu_order' => '14',
                    'usage_type' => 'e-bike',
                ]
            )
        );
    }
}
