<?php

namespace Database\Factories;

use App\Models\GeographicalInformation;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeographicalInformationFactory extends Factory
{
    protected $model = GeographicalInformation::class;

    public function definition()
    {
        return [
            'points' => 'non-sense',
        ];
    }
}
