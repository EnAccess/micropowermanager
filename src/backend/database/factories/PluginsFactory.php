<?php

namespace Database\Factories;

use App\Models\Plugins;
use Illuminate\Database\Eloquent\Factories\Factory;

class PluginsFactory extends Factory {
    protected $model = Plugins::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'mpm_plugin_id' => $this->faker->randomNumber(1, 15),
            'status' => 1,
        ];
    }
}
