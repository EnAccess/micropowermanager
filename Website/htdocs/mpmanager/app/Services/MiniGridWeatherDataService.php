<?php

namespace App\Services;

use App\Models\WeatherData;

class MiniGridWeatherDataService extends BaseService
{

    public function __construct(private WeatherData $weatherData)
  
    {
        parent::__construct([$weatherData]);
    }

}