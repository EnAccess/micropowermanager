<?php

namespace Database\Factories;

use App\Models\MiniGrid;
use Faker\Provider\en_NG\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

class MiniGridFactory extends Factory {
    protected $model = MiniGrid::class;

    public function __construct(
    ) {
        parent::__construct(...func_get_args());
        $this->faker->addProvider(new Address($this->faker));
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'name' => 'MiniGrid '.$this->faker->region,
        ];
    }
}
