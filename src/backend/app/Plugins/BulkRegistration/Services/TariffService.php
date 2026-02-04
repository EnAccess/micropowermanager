<?php

namespace App\Plugins\BulkRegistration\Services;

use App\Models\MainSettings;
use App\Models\Tariff;

class TariffService extends CreatorService {
    public function __construct(Tariff $tariff) {
        parent::__construct($tariff);
    }

    /**
     * @param array<string, mixed> $csvData
     */
    public function resolveCsvDataFromComingRow(array $csvData): Tariff {
        $tariffConfig = config('bulk-registration.csv_fields.tariff');

        $tariffData = [
            'name' => $csvData[$tariffConfig['name']],
            'price' => $csvData[$tariffConfig['price']],
            'currency' => MainSettings::query()->first()->currency,
            'total_price' => $csvData[$tariffConfig['price']],
        ];

        return $this->createRelatedDataIfDoesNotExists($tariffData);
    }
}
