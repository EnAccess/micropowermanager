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
                    'url_slug' => '',
                    'md_icon' => 'home',
                    'menu_order' => '1',

                ],
                [
                    'name' => 'Customers',
                    'url_slug' => '/people/page/1',
                    'md_icon' => 'supervisor_account',
                    'menu_order' => '2',
                ],
                [
                    'name' => 'Agents',
                    'url_slug' => '',
                    'md_icon' => 'support_agent',
                    'menu_order' => '3',
                ],
                [
                    'name' => 'Meters',
                    'url_slug' => '',
                    'md_icon' => 'bolt',
                    'menu_order' => '4',
                ],
                [
                    'name' => 'Transactions',
                    'url_slug' => '/transactions/page/1',
                    'md_icon' => 'account_balance',
                    'menu_order' => '5',
                ],
                [
                    'name' => 'Tickets',
                    'url_slug' => '',
                    'md_icon' => 'confirmation_number',
                    'menu_order' => '6',
                ],
                [
                    'name' => 'Tariffs',
                    'url_slug' => '/tariffs',
                    'md_icon' => 'widgets',
                    'menu_order' => '7',
                ],
                [
                    'name' => 'Targets',
                    'url_slug' => '/targets',
                    'md_icon' => 'gps_fixed',
                    'menu_order' => '8',
                ],
                [
                    'name' => 'Reports',
                    'url_slug' => '/reports',
                    'md_icon' => 'text_snippet',
                    'menu_order' => '9',
                ],
                [
                    'name' => 'Messages',
                    'url_slug' => '',
                    'md_icon' => 'sms',
                    'menu_order' => '10',
                ],
                [
                    'name' => 'Asset Types',
                    'url_slug' => '/assets/types/page/1',
                    'md_icon' => 'devices_other',
                    'menu_order' => '11',
                ],
                [
                    'name' => 'Maintenance',
                    'url_slug' => '/maintenance',
                    'md_icon' => 'home_repair_service',
                    'menu_order' => '12',
                ],


            )
        );
    }
}
