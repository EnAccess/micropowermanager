<?php

namespace App\Services;

use App\Models\Meter\MeterParameter;
use App\Models\Meter\MeterTariff;

class MeterTariffMeterParameterService
{
    public function __construct(
        private MeterTariff $meterTariff,
        private MeterParameter $meterParameter
    ) {
    }

    public function getCountById($meterTariffId): array
    {
        $count = $this->meterParameter->newQuery()->whereHas(
            'meter',
            function ($q) {
                $q->in_use = 1;
            }
        )->where('tariff_id', $meterTariffId)->count();

        return ['count' => $count];
    }

    public function changeMetersTariff($meterTariffIdFrom, $meterTariffIdTo): array
    {

        return  $this->meterParameter->newQuery()->where('tariff_id', $meterTariffIdFrom)
            ->get()
            ->each(function ($meterParameter) use ($meterTariffIdTo) {
                $meterParameter->tariff_id = $meterTariffIdTo;
                $meterParameter->update();
                $meterParameter->save();
            });
    }
}
