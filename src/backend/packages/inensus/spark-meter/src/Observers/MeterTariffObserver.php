<?php

namespace Inensus\SparkMeter\Observers;

use App\Models\AccessRate\AccessRate;
use App\Models\Meter\MeterTariff;
use Inensus\SparkMeter\Helpers\SmTableEncryption;
use Inensus\SparkMeter\Models\SmTariff;
use Inensus\SparkMeter\Services\TariffService;

class MeterTariffObserver {
    private $tariffService;
    private $smTableEncryption;
    private $smTariff;
    private $accessRate;

    public function __construct(
        TariffService $tariffService,
        SmTableEncryption $smTableEncryption,
        SmTariff $smTariff,
        AccessRate $accessRate,
    ) {
        $this->tariffService = $tariffService;
        $this->smTableEncryption = $smTableEncryption;
        $this->smTariff = $smTariff;
        $this->accessRate = $accessRate;
    }

    public function updated(MeterTariff $tariff) {
        $smTariff = $this->smTariff->newQuery()->where('mpm_tariff_id', $tariff->id)->first();
        if ($smTariff) {
            $sparkTariff = $this->tariffService->getSparkTariffInfo($smTariff->tariff_id);
            $tous = [];
            foreach ($tariff->tou as $key => $tou) {
                $tous[$key] = [
                    'start' => $tou['start'],
                    'end' => $tou['end'],
                    'value' => $tou['value'],
                ];
            }
            $accessRate = $this->accessRate->newQuery()->where('tariff_id', $tariff->id)->first();
            $tariffData = [
                'id' => $smTariff->tariff_id,
                'name' => $tariff->name,
                'flat_price' => $tariff->price / 100,
                'flat_load_limit' => $smTariff->flat_load_limit,
                'daily_energy_limit_enabled' => $sparkTariff['daily_energy_limit_enabled'],
                'daily_energy_limit_value' => $sparkTariff['daily_energy_limit_value'],
                'daily_energy_limit_reset_hour' => $sparkTariff['daily_energy_limit_reset_hour'],
                'tou_enabled' => count($tous) > 0,
                'tous' => $tous,
                'plan_enabled' => $accessRate !== null,
                'plan_duration' => $smTariff->plan_duration,
                'plan_price' => $smTariff->plan_price,
                'planFixedFee' => $accessRate !== null ? $accessRate->amount : 0,
            ];

            $updatedTariff = $this->tariffService->updateSparkTariffInfo($tariffData);
            $modelTouString = '';
            foreach ($updatedTariff['tous'] as $item) {
                $modelTouString .= $item['start'].$item['end'].doubleval($item['value']);
            }
            $modelHash = $this->smTableEncryption->makeHash([
                $updatedTariff['name'],
                (int) $updatedTariff['flat_price'],
                $modelTouString,
            ]);
            $smTariff->update([
                'hash' => $modelHash,
            ]);
        }
    }
}
