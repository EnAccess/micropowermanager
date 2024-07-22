<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProtectedPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::connection('micro_power_manager')->table('protected_pages')->insert([
            ['name' => '/locations/add-cluster'],
            ['name' => '/locations/add-mini-grid'],
            ['name' => '/locations/add-village'],
            ['name' => '/settings'],
            ['name' => '/connection-groups'],
            ['name' => '/connection-types'],
            ['name' => '/profile/management'],
            ['name' => '/commissions'],
            ['name' => '/tariffs'],
            ['name' => '/targets'],
            ['name' => '/swifta-payment/swifta-payment-overview'],
            ['name' => '/bulk-registration/bulk-registration'],
            ['name' => '/wave-money/wave-money-overview'],
            ['name' => '/sun-king-meters/sun-king-overview'],
            ['name' => '/kelin-meters/kelin-overview'],
            ['name' => '/kelin-meters/kelin-setting'],
            ['name' => '/spark-meters/sm-overview'],
            ['name' => '/spark-meters/sm-site'],
            ['name' => '/spark-meters/sm-tariff'],
            ['name' => '/spark-meters/sm-sales-account'],
            ['name' => '/spark-meters/sm-setting'],
            ['name' => '/steama-meters/steama-overview'],
            ['name' => '/steama-meters/steama-site'],
            ['name' => '/steama-meters/steama-setting'],
            ['name' => '/stron-meters/stron-overview'],
            ['name' => '/viber-messaging/viber-overview'],
            ['name' => '/calin-meters/calin-overview'],
            ['name' => '/calin-smart-meters/calin-smart-overview'],
            ['name' => '/micro-star-meters/micro-star-overview'],
            ['name' => '/gome-long-meters/gome-long-overview'],
        ]);
    }
}
