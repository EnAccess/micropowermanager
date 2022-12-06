<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubMenuItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('sub_menu_items')->insert(array(
                [
                    'name' => 'Clusters',
                    'url_slug' => '/',
                    'parent_id' => '1',
                ],
                [
                    'name' => 'Mini-Grid',
                    'url_slug' => '/dashboards/mini-grid',
                    'parent_id' => '1',
                ],
                [
                    'name' => 'List',
                    'url_slug' => '/agents/page/1',
                    'parent_id' => '3',
                ],
                [
                    'name' => 'Commission Types',
                    'url_slug' => '/commissions',
                    'parent_id' => '3',
                ],
                [
                    'name' => 'List',
                    'url_slug' => '/meters/page/1',
                    'parent_id' => '4',
                ],
                [
                    'name' => 'Types',
                    'url_slug' => '/meters/types',
                    'parent_id' => '4',
                ],
                [
                    'name' => 'List',
                    'url_slug' => '/tickets',
                    'parent_id' => '6',
                ],
                [
                    'name' => 'Users',
                    'url_slug' => '/tickets/settings/users',
                    'parent_id' => '6',
                ],
                [
                    'name' => 'Categories',
                    'url_slug' => '/tickets/settings/categories',
                    'parent_id' => '6',
                ],
                [
                    'name' => 'List',
                    'url_slug' => '/sms/list',
                    'parent_id' => '10',
                ],
                [
                    'name' => 'New Message',
                    'url_slug' => '/sms/newsms',
                    'parent_id' => '10',
                ],
            )
        );
    }
}
