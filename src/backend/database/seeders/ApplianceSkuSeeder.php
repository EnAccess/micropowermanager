<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asset;
use App\Models\AssetType;

class ApplianceSkuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $electronicsType = AssetType::where('name', 'Electronics')->first();
        $goodsType       = AssetType::where('name', 'Goods')->first();

        if (!$electronicsType || !$goodsType) {
            $this->command->warn('Required Asset Types (Electronics, Goods) not found. Skipping ApplianceSkuSeeder.');
            return;
        }

        Asset::factory()->create([
            'asset_type_id' => $electronicsType->id,
            'name'          => 'SuperVision HD TV ',
            'price'         => 250000,
        ]);

        Asset::factory()->create([
            'asset_type_id' => $goodsType->id,
            'name'          => 'Ice Cubes (5kg)',
            'price'         => 5000,
        ]);

        $this->command->info('Appliance SKUs (Electronics & Goods) seeded successfully!');
    }
}
