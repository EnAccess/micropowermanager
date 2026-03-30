<?php

namespace Database\Seeders;

use App\Models\Appliance;
use App\Models\ApplianceType;
use Illuminate\Database\Seeder;

class ApplianceSkuSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $electronicsType = ApplianceType::where('name', 'Electronics')->first();
        $goodsType = ApplianceType::where('name', 'Goods')->first();

        if (!$electronicsType || !$goodsType) {
            $this->command->outputComponents()->warn('Required Appliance Types (Electronics, Goods) not found. Skipping ApplianceSkuSeeder.');

            return;
        }

        Appliance::factory()->create([
            'appliance_type_id' => $electronicsType->id,
            'name' => 'SuperVision HD TV ',
            'price' => 250000,
        ]);

        Appliance::factory()->create([
            'appliance_type_id' => $goodsType->id,
            'name' => 'Ice Cubes (5kg)',
            'price' => 5000,
        ]);

        $this->command->outputComponents()->info('Appliance SKUs (Electronics & Goods) seeded successfully!');
    }
}
