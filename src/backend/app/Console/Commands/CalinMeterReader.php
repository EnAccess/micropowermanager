<?php

namespace App\Console\Commands;

use App\ManufacturerApi\CalinReadMeter;
use App\Models\Meter\Meter;

/**
 * Reads the daily consumptions of meters
 * Class CalinMeterReader.
 */
class CalinMeterReader extends AbstractSharedCommand
{
    protected $signature = 'calinMeters:readOnline';

    public function __construct(private Meter $meter, private CalinReadMeter $calinReadMeter)
    {
        parent::__construct();
    }

    public function runInCompanyScope(): void
    {
        $meters = $this->meter::whereHas(
            'meterType',
            function ($q) {
                return $q->where('online', 1);
            }
        )->get();

        $readingDate = date('Y-m-d', strtotime('-1 day'));
        $this->calinReadMeter->readBatch(
            $meters,
            1,
            ['date' => $readingDate]
        );
    }
}
