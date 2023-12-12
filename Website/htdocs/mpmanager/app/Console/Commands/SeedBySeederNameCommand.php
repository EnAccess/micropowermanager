<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedBySeederNameCommand extends AbstractSharedCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seeder:with-name {seederName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $seederName = $this->argument('seederName');
            Artisan::call('db:seed', ['--force' => true, '--class' => $seederName]);
        } catch (\Throwable $t) {
            $this->info("failed seeding " . $t->getMessage());
        }
    }
}
