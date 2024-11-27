<?php

namespace Inensus\KelinMeter\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Inensus\KelinMeter\Http\Clients\KelinMeterApiClient;
use Inensus\KelinMeter\Models\KelinMeterMinutelyData;

class MinutelyConsumptionService {
    private $rootUrl = '/getMinData';
    private $kelinApi;
    private $kelinMeterMinutelyData;

    public function __construct(
        KelinMeterApiClient $kelinApi,
        KelinMeterMinutelyData $kelinMeterMinutelyData,
    ) {
        $this->kelinApi = $kelinApi;
        $this->kelinMeterMinutelyData = $kelinMeterMinutelyData;
    }

    public function getMinutelyDataFromAPI() {
        $today = Carbon::now()->format('Ymd');
        $moment = Carbon::now()->format('His');
        $pageNo = 1;

        do {
            $queryParams = [
                'meterType' => 1,
                'startYmd' => $today,
                'startHms' => $moment,
                'endYmd' => $today,
                'endHms' => $moment,
                'pageNo' => $pageNo,
                'pageSize' => 500,
            ];
            try {
                $result = $this->kelinApi->get($this->rootUrl, $queryParams);
                collect($result['data']['minData'])->each(function ($data) {
                    KelinMeterMinutelyData::query()->create([
                        'id_of_terminal' => $data['rtuId'],
                        'id_of_measurement_point' => $data['mpId'],
                        'address_of_meter' => $data['meterAddr'],
                        'name_of_meter' => $data['meterName'],
                        'date_of_data' => $data['date'],
                        'time_of_data' => $data['time'],
                        'positive_active_value' => $data['bdZy'],
                        'positive_reactive_value' => $data['bdZw'],
                        'inverted_active_value' => $data['bdFy'],
                        'inverted_reactive_value' => $data['bdFw'],
                        'positive_active_minute' => $data['dlZy'],
                        'positive_reactive_minute' => $data['dlZw'],
                        'inverted_active_minute' => $data['dlFy'],
                        'inverted_reactive_minute' => $data['dlFw'],
                        'voltage_of_phase_a' => $data['va'],
                        'voltage_of_phase_b' => $data['vb'],
                        'voltage_of_phase_c' => $data['vc'],
                        'power' => $data['p'],
                        'power_factor' => $data['cS'],
                        'reactive_power' => $data['q'],
                        'current_of_phase_a' => $data['ia'],
                        'current_of_phase_b' => $data['ib'],
                        'current_of_phase_c' => $data['ic'],
                        'temperature_1' => $data['tEMP1'],
                        'temperature_2' => $data['tEMP2'],
                        'pressure_1' => $data['pRES1'],
                        'pressure_2' => $data['pRES2'],
                        'flow_velocity' => $data['sPEED'],
                    ]);
                });
                ++$pageNo;
            } catch (\Exception $exception) {
                Log::error('Failed on minutely data request .', ['message' => $exception->getMessage()]);
                $result['data']['dataCount'] = 0;
            }
        } while ($result['data']['dataCount'] > 0);
    }

    public function getDailyData($meterAddress, $perPage) {
        return $this->kelinMeterMinutelyData->newQuery()->where('address_of_meter', $meterAddress)->orderByDesc('id')->paginate($perPage);
    }
}
