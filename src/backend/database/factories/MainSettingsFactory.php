<?php

namespace Database\Factories;

use App\Models\MainSettings as MainSettingsAlias;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class MainSettingsFactory extends Factory {
    protected $model = MainSettingsAlias::class;

    public function definition(): array {
        return [
            'site_title' => 'MPM - The easiest way to manage your Mini-Grid',
            'company_name' => 'MicroPowerManager',
            'currency' => '€',
            'country' => 'Germany',
            'vat_energy' => 1,
            'vat_appliance' => 18,
            'language' => 'en',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
