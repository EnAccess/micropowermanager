<?php

namespace Database\Factories;

use App\Models\Cluster;
use Faker\Provider\en_NG\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClusterFactory extends Factory {
    protected $model = Cluster::class;

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
            'name' => 'Cluster '.$this->faker->county,
            'geo_data' => '{"leaflet_id":416,"type":"manual","geojson":{"type":"Polygon","coordinates":[[[-1.0021831137920607,34.09735878800838],[-1.0037278294879668,34.08104951280351],[-0.9961758791705448,34.08001945331692],[-0.9831315606570004,34.079332746992485],[-0.9745497443261169,34.08036280647911],[-0.9889671831741885,34.09890387723834]]]},"display_name":"My Cluster","selected":true,"draw_type":"draw","lat":-0.991455885101313,"lon":34.08617119747313}',
        ];
    }
}
