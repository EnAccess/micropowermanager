<?php

namespace App\Listeners;

use App\Exceptions\WeatherProviderUnreachable;
use App\Models\MiniGrid;
use App\Models\Solar;
use App\Models\WeatherData;
use App\Services\IWeatherDataProvider;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Class SolarDataListener
 *
 * @package App\Listeners
 * TODO: replace that listener with an model observer.
 */
class SolarDataListener
{
    /**
     * @var IWeatherDataProvider
     */
    private $weatherDataProvider;
    /**
     * @var MiniGrid
     */
    private $miniGrid;

    /**
     * @var WeatherData
     */
    private $weatherData;

    public function __construct(
        WeatherData $weatherData,
        IWeatherDataProvider $weatherDataProvider,
        MiniGrid $miniGrid
    ) {
        $this->weatherData = $weatherData;
        $this->weatherDataProvider = $weatherDataProvider;
        $this->miniGrid = $miniGrid;
    }

    /**
     * @param Solar $solar
     * @param int $miniGridId
     * @throws WeatherProviderUnreachable
     */
    public function onSolarReading(Solar $solar, int $miniGridId): void
    {
        try {
            $miniGridPoints = $this->getMiniGridLocation($miniGridId);
        } catch (ModelNotFoundException $x) {
            Log::critical("Weather forecast reading failed for mini-grid:" .
                " $miniGridId. $miniGridId does not exist in the database");
            return;
        } catch (Exception $x) {
            Log::critical("Weather forecast reading failed for mini-grid:" .
                " $miniGridId. Reading of geo location points failed");
            return;
        }


        $currentWeather = $this->weatherDataProvider->getCurrentWeatherData($miniGridPoints);
        if ($currentWeather->getStatusCode() !== 200) {
            throw new WeatherProviderUnreachable(
                'Current weather data is not available ' . $currentWeather->getBody(),
                $currentWeather->getStatusCode()
            );
        }
        $currentWeatherData = $currentWeather->getBody();


        $forecastWeather = $this->weatherDataProvider->getWeatherForeCast($miniGridPoints);
        if ($forecastWeather->getStatusCode() !== 200) {
            throw new WeatherProviderUnreachable(
                'Current weather data is not available ' . $forecastWeather->getBody(),
                $forecastWeather->getStatusCode()
            );
        }
        $forecastWeatherData = $forecastWeather->getBody();

        $date = Carbon::parse($solar->time_stamp);
        $currentWeatherFileName = 'current-' . $solar->node_id . $solar->device_id . $date->timestamp . '.json';
        $forecastWeatherFileName = 'forecast-' . $solar->node_id . $solar->device_id . $date->timestamp . '.json';
        $this->weatherData->newQuery()
            ->create(
                [
                    'solar_id' => $solar->id,
                    'current_weather_data' => $currentWeatherFileName,
                    'forecast_weather_data' => $forecastWeatherFileName,
                    'record_time' => $date->timestamp,
                ]
            );

        $this->storeWeatherData($currentWeatherFileName, (string)$currentWeatherData);
        $this->storeWeatherData($forecastWeatherFileName, (string)$forecastWeatherData);
    }


    private function storeWeatherData(string $fileName, string $data): void
    {
        Storage::disk('local')->put('solar-reading/' . $fileName, $data);
    }

    /**
     * @param int $miniGridId
     * @return string[]
     *
     * @psalm-return non-empty-list<string>
     */
    private function getMiniGridLocation(int $miniGridId): array
    {
        $miniGrid = $this->miniGrid
            ::with('location')
            ->findOrFail($miniGridId);

        return explode(',', $miniGrid->location->points);
    }


    public function subscribe(Dispatcher $events): void
    {
        $events->listen('solar.received', '\App\Listeners\SolarDataListener@onSolarReading');
    }
}
