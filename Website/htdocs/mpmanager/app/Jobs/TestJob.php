<?php

namespace App\Jobs;

use App\Http\Middleware\UserDefaultDatabaseConnectionMiddleware;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestJob extends AbstractJob
{
    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    public function handle()
    {
        dump("brnako");
    }
}
