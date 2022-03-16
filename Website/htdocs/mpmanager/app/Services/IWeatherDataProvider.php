<?php

namespace App\Services;

use Psr\Http\Message\ResponseInterface;

interface IWeatherDataProvider
{
    public function getCurrentWeatherData(array $geoLocation): ResponseInterface;


    public function getWeatherForeCast(array $geoLocation): ResponseInterface;
}
