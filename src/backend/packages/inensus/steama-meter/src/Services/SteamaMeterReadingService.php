<?php

namespace Inensus\SteamaMeter\Services;

use App\Models\Meter\MeterConsumption;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Inensus\SteamaMeter\Exceptions\SteamaApiResponseException;
use Inensus\SteamaMeter\Http\Clients\SteamaMeterApiClient;
use Inensus\SteamaMeter\Models\SteamaMeter;

class SteamaMeterReadingService {
    public function __construct(
        private SteamaMeter $steamaMeter,
        private SteamaMeterApiClient $steamaApi,
        private MeterConsumption $meterConsumtion,
    ) {}

    public function getMeterReadingsThroughHourlyWorkingJob(): void {
        $now = Carbon::now()->toIso8601ZuluString();
        $oneHourEarlier = Carbon::now()->subHours(10)->toIso8601ZuluString();
        $this->steamaMeter->newQuery()->get()->each(function ($meter) use ($now, $oneHourEarlier) {
            $url = '/meters/'.$meter->meter_id.'/utilities/1/readings/?start_time='.$oneHourEarlier.'&end_time='.$now;
            try {
                $result = $this->steamaApi->get($url);
                $readings = $result['results'];
                if (count($readings) > 0) {
                    // @phpstan-ignore argument.templateType,argument.templateType
                    collect($readings)->each(function (array $reading) use ($meter) {
                        $this->meterConsumtion->newQuery()
                            ->updateOrCreate(
                                [
                                    'meter_id' => $meter->mpm_meter_id,
                                    'reading_date' => Carbon::parse($reading['timestamp'])->format('Y-m-d H:i:s'),
                                ],
                                [
                                    'meter_id' => $meter->mpm_meter_id,
                                    'total_consumption' => $reading['reading'],
                                    'consumption' => $reading['usage_amount'],
                                    'credit_on_meter' => 0,
                                    'reading_date' => Carbon::parse($reading['timestamp'])->format('Y-m-d H:i:s'),
                                ]
                            );
                    });
                }
                usleep(100000);
            } catch (SteamaApiResponseException $e) {
                Log::critical('Meter utility reading failed.', ['message' => $e->getMessage()]);
            }
        });
    }
}
