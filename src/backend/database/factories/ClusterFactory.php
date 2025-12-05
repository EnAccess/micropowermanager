<?php

namespace Database\Factories;

use App\Models\Cluster;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Cluster> */
class ClusterFactory extends Factory {
    protected $model = Cluster::class;

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
            'name' => 'Cluster '.$faker->county(),
            'geo_json' => '{
                "type": "Polygon",
                "coordinates": [
                    [
                        [-1.0021831137920607, 34.09735878800838],
                        [-1.0037278294879668, 34.08104951280351],
                        [-0.9961758791705448, 34.08001945331692],
                        [-0.9831315606570004, 34.079332746992485],
                        [-0.9745497443261169, 34.08036280647911],
                        [-0.9889671831741885, 34.09890387723834]
                    ]
                ]
            }',
        ];
    }
}
