<?php

namespace Database\Factories;

use App\Models\MainSettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MainSettings> */
class MainSettingsFactory extends Factory {
    protected $model = MainSettings::class;

    /**
     * @return array<string, mixed>
     */
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
