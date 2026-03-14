<?php

namespace Database\Factories;

use App\Models\Village;
use Faker\Provider\en_NG\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Village> */
class VillageFactory extends Factory {
    protected $model = Village::class;

    public function __construct(
    ) {
        parent::__construct(...func_get_args());
        $this->faker->addProvider(new Address($this->faker));
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'name' => $this->faker->streetName,
            'country_id' => 1,
        ];
    }
}
