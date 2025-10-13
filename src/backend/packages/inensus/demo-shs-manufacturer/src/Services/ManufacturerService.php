<?php

namespace Inensus\DemoShsManufacturer\Services;

use App\Models\Manufacturer;
use Illuminate\Support\Facades\Log;

class ManufacturerService {
    public function __construct(private Manufacturer $manufacturer) {}

    public function register(): void {
        try {
            $api = $this->manufacturer->newQuery()->where('api_name', 'DemoShsManufacturerApi')->first();
            if (!$api) {
                $this->manufacturer->newQuery()->create([
                    'name' => 'Demo SHS Manufacturer',
                    'type' => 'shs',
                    'website' => 'https://demo.micropowermanager.com/',
                    'api_name' => 'DemoShsManufacturerApi',
                ]);
            }
        } catch (\Exception) {
            // If tenant database is not available (e.g., during central installation),
            // the manufacturer will be registered when the plugin is activated by a company
            Log::info('Demo SHS Manufacturer registration skipped - tenant database not available');
        }
    }
}
