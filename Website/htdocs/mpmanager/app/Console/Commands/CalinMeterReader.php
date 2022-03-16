<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 28.11.18
 * Time: 15:26
 */

namespace App\Console\Commands;

use App\ManufacturerApi\CalinReadMeter;
use App\Models\Meter\Meter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Reads the daily consumptions of meters
 * Class CalinMeterReader
 *
 * @package App\Console\Commands
 */
class CalinMeterReader extends Command
{

    /**
     * The name and signature of the command
     *
     * @var string
     */
    protected $signature = 'calinMeters:readOnline';

    /**
     * @var Meter
     */
    public $meter;
    /**
     * @var CalinReadMeter
     */
    public $calinReadMeter;

    /**
     * CalinMeterReader constructor.
     *
     * @param Meter $meter
     */
    public function __construct(Meter $meter, CalinReadMeter $calinReadMeter)
    {
        parent::__construct();
        $this->meter = $meter;
        $this->calinReadMeter = $calinReadMeter;
    }

    public function handle(): int
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
        return 0;
    }
}
