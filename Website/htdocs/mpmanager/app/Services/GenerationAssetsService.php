<?php

namespace App\Services;

use App\Helpers\PowerConverter;
use App\Models\Battery;
use App\Models\Energy;
use App\Models\PV;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class GenerationAssetsService
{

    /**
     * @var Battery
     */
    private $battery;
    /**
     * @var PV
     */
    private $pv;
    /**
     * @var Energy
     */
    private $energy;

    public function __construct(Battery $battery, PV $pv, Energy $energy)
    {
        $this->battery = $battery;
        $this->pv = $pv;
        $this->energy = $energy;
    }


    /**
     * @param $miniGridId
     * @return array (mixed|null)[][]
     */
    public function getGenerationAssets($miniGridId): array
    {
        $startDate = request()->input('start_date');
        $endDate = request()->input('end_date');

        $batteryReadings = $this->getBatteryReadings($miniGridId, $startDate, $endDate)->toArray();
        $pvReadings = $this->getPvReadings($miniGridId, $startDate, $endDate)->toArray();
        $energyReadings = $this->getServedEnergyReadings($miniGridId, $startDate, $endDate)->toArray();
        $arrays = array_merge_recursive($batteryReadings, $pvReadings, $energyReadings);
        $arrays = array_map(
            function ($a) {
                $a['read_key'] = is_array($a['read_key']) ?
                    $a['read_key'][0]
                    : $a['read_key'];
                $a['data_reading_date'] = is_array($a['data_reading_date']) ?
                    $a['data_reading_date'][0]
                    : $a['data_reading_date'];
                $a['data_reading_time'] = is_array($a['data_reading_time']) ?
                    $a['data_reading_time'][0]
                    : $a['data_reading_time'];

                $a = $this->fillArray($a);

                if ($a['new_generated_energy'] !== null) {
                    $a['new_generated_energy'] = PowerConverter::convert(
                        $a['new_generated_energy'],
                        $a['new_generated_energy_unit'],
                        'kWh'
                    );
                    $a['new_generated_energy_unit'] = 'kWh';
                }

                if ($a['d_newly_energy'] !== null) {
                    $a['d_newly_energy'] = PowerConverter::convert(
                        $a['d_newly_energy'],
                        $a['d_newly_energy_unit'],
                        'kWh'
                    );
                    $a['d_newly_energy_unit'] = 'kWh';
                }
                if ($a['c_newly_energy'] !== null) {
                    $a['c_newly_energy'] = PowerConverter::convert(
                        $a['c_newly_energy'],
                        $a['c_newly_energy_unit'],
                        'kWh'
                    );
                    $a['c_newly_energy_unit'] = 'kWh';
                }


                if (
                    $a['c_newly_energy'] !== null
                    && $a['d_newly_energy'] !== null
                    && $a['new_generated_energy'] !== null
                    && $a['absorbed_energy_since_last'] !== null
                ) {
                    $a['energyFromDieselGen'] = PowerConverter::convert(
                        $a['absorbed_energy_since_last'],
                        $a['absorbed_energy_since_last_unit'],
                        'kWh'
                    ) -
                        PowerConverter::convert($a['d_newly_energy'], $a['d_newly_energy_unit'], 'kWh') -
                        PowerConverter::convert($a['new_generated_energy'], $a['new_generated_energy_unit'], 'kWh') +
                        PowerConverter::convert($a['c_newly_energy'], $a['c_newly_energy_unit'], 'kWh');
                    if ($a['energyFromDieselGen'] < 0) {
                        $a['energyFromDieselGen'] = 0;
                    }
                }

                return $a;
            },
            $arrays
        );
        ksort($arrays);

        return $arrays;
    }


    /**
     * fills the array if the given array doesnt contain all required fields
     *
     * @param  $a
     * @return array
     */
    private function fillArray($a): array
    {
        $baseArray = [
            "d_newly_energy" => 0,
            "d_newly_energy_unit" => "Wh",
            "new_generated_energy" => 0,
            "new_generated_energy_unit" => "kWh",
            "absorbed_energy_since_last" => 0,
            "absorbed_energy_since_last_unit" => 'kWh',
            "energyFromDieselGen" => 0,
            "c_newly_energy" => 0,
            "c_newly_energy_unit" => 'kWh',
        ];
        return array_merge($baseArray, $a);
    }

    /**
     * @param $miniGridId
     * @param $startDate
     * @param $endDate
     * @return Collection
     */
    private function getBatteryReadings($miniGridId, $startDate, $endDate): Collection
    {
        $readings = $this->battery::query()
            ->select(DB::raw("CONCAT(DATE(read_out),\"-\"," .
                "DATE_FORMAT(SEC_TO_TIME(FLOOR((TIME_TO_SEC(read_out)+450)/900)*900),\"%H-%i-%S\")) as read_key, " .
                "DATE(read_out) as data_reading_date,SEC_TO_TIME(FLOOR((TIME_TO_SEC(read_out)+450)/900)*900) " .
                "as data_reading_time, d_newly_energy, d_newly_energy_unit,c_newly_energy, c_newly_energy_unit"))
            ->where('mini_grid_id', $miniGridId);

        if ($startDate) {
            $readings->whereDate('read_out', '>=', $startDate);
        }
        if ($endDate) {
            $readings->whereDate('read_out', '<=', $endDate);
        }
        return $readings
            ->orderBy('read_out')
            ->get()->keyBy('read_key');
    }

    /**
     * @param $miniGridId
     * @param $startDate
     * @param $endDate
     * @return GenerationAssetsService|Builder[]|Collection
     */
    private function getPvReadings($miniGridId, $startDate, $endDate)
    {
        $readings = $this->pv::query()
            ->select(DB::raw("CONCAT(DATE(reading_date),\"-\"," .
                "DATE_FORMAT(SEC_TO_TIME(FLOOR((TIME_TO_SEC(reading_date)+450)/900)*900),\"%H-%i-%S\")) as read_key," .
                " DATE(reading_date) as data_reading_date,SEC_TO_TIME(FLOOR((TIME_TO_SEC(reading_date)+450)/900)*900)" .
                " as data_reading_time, new_generated_energy, new_generated_energy_unit"))
            ->where('mini_grid_id', $miniGridId);

        if ($startDate) {
            $readings->whereDate('reading_date', '>=', $startDate);
        }
        if ($endDate) {
            $readings->whereDate('reading_date', '<=', $endDate);
        }
        return $readings
            ->orderBy('reading_date')
            ->get()->keyBy('read_key');
    }

    /**
     * @param $miniGridId
     * @param $startDate
     * @param $endDate
     * @return Collection
     */
    private function getServedEnergyReadings($miniGridId, $startDate, $endDate): Collection
    {
        $readings = $this->energy::query()
            ->select(DB::raw("CONCAT(DATE(read_out),\"-\"," .
                "DATE_FORMAT(SEC_TO_TIME(FLOOR((TIME_TO_SEC(read_out)+450)/900)*900),\"%H-%i-%S\")) as read_key," .
                " DATE(read_out) as data_reading_date,SEC_TO_TIME(FLOOR((TIME_TO_SEC(read_out)+450)/900)*900) " .
                "as data_reading_time, absorbed_energy_since_last,absorbed_energy_since_last_unit"))
            ->where('mini_grid_id', $miniGridId);

        if ($startDate) {
            $readings->whereDate('read_out', '>=', $startDate);
        }
        if ($endDate) {
            $readings->whereDate('read_out', '<=', $endDate);
        }
        return $readings
            ->orderBy('read_out')
            ->get()->keyBy('read_key');
    }
}
