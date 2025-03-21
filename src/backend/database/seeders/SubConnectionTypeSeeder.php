<?php

namespace Database\Seeders;

use App\Models\SubConnectionType;
use App\Services\CompanyService;
use Illuminate\Database\Seeder;

class SubConnectionTypeSeeder extends Seeder {
    public function __construct(
        private CompanyService $companyService,
    ) {
        $this->companyService->buildDatabaseConnectionDemoCompany();
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $data = [
            ['name' => 'CU', 'connection_type_id' => 2, 'tariff_id' => 3],
            ['name' => 'Bar', 'connection_type_id' => 2, 'tariff_id' => 3],
            ['name' => 'Cafeteria', 'connection_type_id' => 2, 'tariff_id' => 3],
            ['name' => 'Carpenter', 'connection_type_id' => 2, 'tariff_id' => 3],
            ['name' => 'Compresor shop', 'connection_type_id' => 2, 'tariff_id' => 3],
            ['name' => 'Guest house', 'connection_type_id' => 2, 'tariff_id' => 3],
            ['name' => 'HH', 'connection_type_id' => 2, 'tariff_id' => 3],
            ['name' => 'Iron box', 'connection_type_id' => 2, 'tariff_id' => 3],
            ['name' => 'Laundry machine', 'connection_type_id' => 2, 'tariff_id' => 3],
            ['name' => 'Mobile charging center', 'connection_type_id' => 2, 'tariff_id' => 3],
            ['name' => 'Saloon', 'connection_type_id' => 2, 'tariff_id' => 3],
            ['name' => 'Shop fridge', 'connection_type_id' => 2, 'tariff_id' => 3],
            ['name' => 'Stationery', 'connection_type_id' => 2, 'tariff_id' => 3],
            ['name' => 'Tailor', 'connection_type_id' => 2, 'tariff_id' => 3],
            ['name' => 'Video show', 'connection_type_id' => 2, 'tariff_id' => 3],
            ['name' => 'Residential', 'connection_type_id' => 21, 'tariff_id' => 20],
            ['name' => 'Business', 'connection_type_id' => 22, 'tariff_id' => 20],
            ['name' => 'Institution', 'connection_type_id' => 23, 'tariff_id' => 20],
        ];

        foreach ($data as $item) {
            SubConnectionType::factory()->create($item);
        }
    }
}
