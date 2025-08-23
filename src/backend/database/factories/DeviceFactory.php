<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Device> */
class DeviceFactory extends Factory {
    protected $model = Device::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'device_serial' => $this->faker->unique()->uuid,
        ];
    }
}
