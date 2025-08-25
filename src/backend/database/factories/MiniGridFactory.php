<?php

namespace Database\Factories;

use App\Models\MiniGrid;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MiniGrid> */
class MiniGridFactory extends Factory {
    protected $model = MiniGrid::class;

    public function __construct(
    ) {
        parent::__construct(...func_get_args());
        $this->faker->addProvider(new \Faker\Provider\en_NG\Address($this->faker));
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        // @phpstan-ignore-next-line varTag.unresolvableType
        /** @var \Faker\Generator&\Faker\Provider\en_NG\Address */
        $faker = $this->faker;

        return [
            'name' => 'MiniGrid '.$faker->region(),
        ];
    }
}
