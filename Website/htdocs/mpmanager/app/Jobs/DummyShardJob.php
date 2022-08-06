<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;

class DummyShardJob
{

    public function handle()
    {
        Log::critical('DUMMY SHARD JOB', ['db' => config('database.connections.shard')]);
    }
}
