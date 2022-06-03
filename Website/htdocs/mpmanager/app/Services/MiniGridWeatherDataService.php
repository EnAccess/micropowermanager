<?php

namespace App\Services;

use App\Models\WeatherData;

class MiniGridWeatherDataService
{

    public function __construct(private WeatherData $weatherData)
    {
    }

}
