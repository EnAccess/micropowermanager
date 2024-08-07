<?php

namespace App\Services\Interfaces;

use Psr\Http\Message\ResponseInterface;

interface IWeatherDataProvider
{
    public function getCurrentWeatherData(array $geoLocation): ResponseInterface;

    public function getWeatherForeCast(array $geoLocation): ResponseInterface;
}
