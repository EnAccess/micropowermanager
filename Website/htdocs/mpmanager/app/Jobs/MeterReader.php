<?php

namespace App\Jobs;

use App\ManufacturerApi\CalinReadMeter;
use App\Models\Meter\Meter;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class MeterReader extends AbstractJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var CalinReadMeter
     */
    public $meterReader;
    /**
     * @var Meter
     */
    public $meters;

    /**
     * Create a new job instance.
     *
     * @param CalinReadMeter $meterReader
     * @param Meter          $meters
     */
    public function __construct(CalinReadMeter $meterReader, Meter $meters)
    {
        $this->meterReader = $meterReader;
        $this->meters = $meters;
        parent::__construct(get_class($this));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function executeJob(): void
    {
        $this->meterReader->readBatch($this->meters->get(), $this->meterReader::READ_DAILY, ['date' => date('Y-m-d')]);
    }
}
