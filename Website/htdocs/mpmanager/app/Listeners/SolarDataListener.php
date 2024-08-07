<?php

namespace App\Listeners;

use App\Exceptions\WeatherProviderUnreachable;
use App\Models\MiniGrid;
use App\Models\Solar;
use App\Models\WeatherData;
use App\Services\Interfaces\IWeatherDataProvider;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Class SolarDataListener.
 */
class SolarDataListener
{
    public function __construct(
        private WeatherData $weatherData,
        private IWeatherDataProvider $weatherDataProvider,
        private MiniGrid $miniGrid
    ) {
    }

    /**
     * @param Solar $solar
     * @param int   $miniGridId
     *
     * @throws WeatherProviderUnreachable
     */
    public function onSolarReading(Solar $solar, int $miniGridId): void
    {
        try {
            $miniGridPoints = $this->getMiniGridLocation($miniGridId);
        } catch (ModelNotFoundException $x) {
            Log::critical('Weather forecast reading failed for mini-grid:'.
                " $miniGridId. $miniGridId does not exist in the database");

            return;
        } catch (\Exception $x) {
            Log::critical('Weather forecast reading failed for mini-grid:'.
                " $miniGridId. Reading of geo location points failed");

            return;
        }

        $currentWeather = $this->weatherDataProvider->getCurrentWeatherData($miniGridPoints);
        if ($currentWeather->getStatusCode() !== 200) {
            throw new WeatherProviderUnreachable('Current weather data is not available '.$currentWeather->getBody(), $currentWeather->getStatusCode());
        }
        $currentWeatherData = $currentWeather->getBody();

        $forecastWeather = $this->weatherDataProvider->getWeatherForeCast($miniGridPoints);
        if ($forecastWeather->getStatusCode() !== 200) {
            throw new WeatherProviderUnreachable('Current weather data is not available '.$forecastWeather->getBody(), $forecastWeather->getStatusCode());
        }
        $forecastWeatherData = $forecastWeather->getBody();

        $date = Carbon::parse($solar->time_stamp);
        $currentWeatherFileName = 'current-'.$solar->node_id.$solar->device_id.$date->timestamp.'.json';
        $forecastWeatherFileName = 'forecast-'.$solar->node_id.$solar->device_id.$date->timestamp.'.json';
        $this->weatherData->newQuery()
            ->create(
                [
                    'solar_id' => $solar->id,
                    'current_weather_data' => $currentWeatherFileName,
                    'forecast_weather_data' => $forecastWeatherFileName,
                    'record_time' => $date->timestamp,
                ]
            );

        $this->storeWeatherData($currentWeatherFileName, (string) $currentWeatherData, $solar);
        $this->storeWeatherData($forecastWeatherFileName, (string) $forecastWeatherData, $solar);
    }

    private function storeWeatherData(string $fileName, string $data, $solar): void
    {
        $storageFolder = $solar->storage_folder;
        $miniGridId = $solar->mini_grid_id;
        $path = storage_path("/app/public/$storageFolder/$miniGridId/public");

        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0777, true, true);
        }

        Storage::disk('local')->put("/public/$storageFolder/$miniGridId/public/".$fileName, $data);
    }

    /**
     * @param int $miniGridId
     *
     * @return string[]
     *
     * @psalm-return non-empty-list<string>
     */
    private function getMiniGridLocation(int $miniGridId): array
    {
        $miniGrid = $this->miniGrid::with('location')
            ->findOrFail($miniGridId);

        return explode(',', $miniGrid->location->points);
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen('solar.received', '\App\Listeners\SolarDataListener@onSolarReading');
    }
}
