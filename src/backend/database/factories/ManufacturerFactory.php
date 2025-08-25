<?php

namespace Database\Factories;

use App\Models\Manufacturer;
use Doctrine\Inflector\InflectorFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Manufacturer> */
class ManufacturerFactory extends Factory {
    protected $model = Manufacturer::class;

    /**
     * Indicate that the manufacturer is for meter devices.
     */
    public function isMeterManufacturer(): static {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'meter',
            ];
        });
    }

    /**
     * Indicate that the manufacturer is for SHS devices.
     */
    public function isShsManufacturer(): static {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'shs',
            ];
        });
    }

    public function definition(): array {
        $inflector = InflectorFactory::create()->build();

        $companyName = $this->faker->company;

        return [
            'name' => $companyName,
            'website' => $this->faker->url,
            'contact_person' => $this->faker->name,
            'api_name' => $inflector->classify($inflector->urlize($companyName)).'Api',
        ];
    }
}
