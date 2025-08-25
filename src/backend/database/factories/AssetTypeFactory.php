<?php

namespace Database\Factories;

use App\Models\AssetType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssetType> */
class AssetTypeFactory extends Factory {
    protected $model = AssetType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'name' => $this->faker->word,
        ];
    }
}
