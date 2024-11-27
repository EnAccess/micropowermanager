<?php

namespace Inensus\KelinMeter\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Inensus\KelinMeter\Http\Clients\KelinMeterApiClient;
use Inensus\KelinMeter\Models\KelinMeterDailyData;

class DailyConsumptionService {
    private $rootUrl = '/getDayData';
    private $kelinApi;
    private $kelinMeterDailyData;

    public function __construct(
        KelinMeterApiClient $kelinApi,
        KelinMeterDailyData $kelinMeterDailyData,
    ) {
        $this->kelinApi = $kelinApi;
        $this->kelinMeterDailyData = $kelinMeterDailyData;
    }

    public function getDailyDataFromAPI() {
        $startDay = Carbon::now()->subDays(1)->format('Ymd');
        $endDay = Carbon::now()->format('His');
        $pageNo = 1;
        do {
            $queryParams = [
                'meterType' => 1,
                'startYmd' => $startDay,
                'endYmd' => $endDay,
                'pageNo' => $pageNo,
                'pageSize' => 500,
            ];
            try {
                $result = $this->kelinApi->get($this->rootUrl, $queryParams);
                collect($result['data']['dayData'])->each(function ($data) {
                    KelinMeterDailyData::query()->create([
                        'id_of_terminal' => $data['rtuId'],
                        'id_of_measurement_point' => $data['mpId'],
                        'address_of_meter' => $data['meterAddr'],
                        'name_of_meter' => $data['meterName'],
                        'date_of_data' => $data['date'],
                        'total_positive_active_power_cumulative_flow_indication' => $data['bdZy'],
                        'total_positive_active_peak_power' => $data['bdZyFl1'],
                        'total_positive_active_flat_power' => $data['bdZyFl2'],
                        'total_positive_active_valley_power' => $data['bdZyFl3'],
                        'total_positive_active_spike_power' => $data['bdZyFl4'],
                        'total_positive_reactive_power_cumulative_flow_indication' => $data['bdZw'],
                        'total_positive_reactive_peak_power' => $data['bdZwFl1'],
                        'total_positive_reactive_flat_power' => $data['bdZwFl2'],
                        'total_positive_reactive_valley_power' => $data['bdZwFl3'],
                        'total_positive_reactive_spike_power' => $data['bdZwFl4'],
                        'total_reverted_active_power_cumulative_flow_indication' => $data['bdFy'],
                        'total_reverted_reactive_power_cumulative_flow_indication' => $data['bdFw'],
                        'positive_active_total_daily_power' => $data['dlZy'],
                        'positive_active_daily_power_in_peak' => $data['dlZyFl1'],
                        'positive_active_daily_power_in_flat' => $data['dlZyFl2'],
                        'positive_active_daily_power_in_valley' => $data['dlZyFl3'],
                        'positive_active_daily_power_in_spike' => $data['dlZyFl4'],
                        'positive_reactive_total_daily_power' => $data['dlZw'],
                        'reverted_active_total_daily_power' => $data['dlFy'],
                        'reverted_reactive_total_daily_power' => $data['dlFw'],
                    ]);
                });
                ++$pageNo;
            } catch (\Exception $exception) {
                Log::error('Failed on daily data request .', ['message' => $exception->getMessage()]);
                $result['dataCount'] = 0;
            }
        } while ($result['dataCount'] > 0);
    }

    public function getDailyData($meterAddress, $perPage) {
        return $this->kelinMeterDailyData->newQuery()->where('address_of_meter', $meterAddress)->orderByDesc('id')->paginate($perPage);
    }
}
