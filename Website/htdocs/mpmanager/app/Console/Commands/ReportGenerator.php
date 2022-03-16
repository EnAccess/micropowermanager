<?php

namespace App\Console\Commands;

use App\Http\Controllers\Reports;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReportGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:city-revenue {type} {--start-date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates city revenue reports';

    public $reports;

    /**
     * Create a new command instance.
     *
     * @param Reports $reports
     */
    public function __construct(Reports $reports)
    {
        parent::__construct();
        $this->reports = $reports;
    }


    /**
     * Execute the console command.
     */
    public function handle(): void
    {

        $startDay = Carbon::now()->format('Y-m-d');
        if ($this->option('start-date') != "") {
            $toDay = Carbon::parse($this->option('start-date'))->format('Y-m-d');
        } else {
            $toDay = Carbon::now()->subDays(1)->format('Y-m-d');
        }
        if ($this->argument('type') == "weekly") {
            $startDay = Carbon::parse($toDay)->modify("last Monday")->format('Y-m-d');
        } elseif ($this->argument('type') == "monthly") {
            $startDay = Carbon::parse($toDay)->modify("first day of this month")->format('Y-m-d');
        } else {
            var_dump("That the given parameter is not supported and end the process with that");
        }
        $this->reports->generateWithJob($startDay, $toDay, $this->argument('type'));
    }
}
