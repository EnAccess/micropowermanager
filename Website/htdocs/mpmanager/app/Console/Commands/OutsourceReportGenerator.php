<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Inensus\Ticket\Http\Controllers\ExportController;

class OutsourceReportGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:outsource {--start-date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create outsources reports';

    public $reports;

    /**
     * Create a new command instance.
     *
     * @param ExportController $reports
     */
    public function __construct(ExportController $reports)
    {
        parent::__construct();
        $this->reports = $reports;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if ($this->option('start-date') !== "") {
            $toDay = Carbon::parse($this->option('start-date'))->format('Y-m-d');
        } else {
            $toDay = Carbon::now()->subDays(1)->format('Y-m-d');
        }
        $startDay = Carbon::parse($toDay)->modify("first day of this month")->format('Y-m-d');
        $this->reports->outsourceWithJob($startDay, $toDay);
        return 0;
    }
}
