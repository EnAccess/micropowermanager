<?php

namespace App\Console\Commands\MpmSystemChecks;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class RedisConnectionCheckCommand extends Command {
    protected $signature = 'mpm-system-checks:redis-connection';
    protected $description = 'Check Redis connection and configuration';

    public function handle(): int {
        $this->info('Checking Redis connection...');

        try {
            Redis::connection()->ping();
            $this->info('Redis connection successful.');

            $testKey = 'mpm:system-check:'.time();
            Redis::connection()->setex($testKey, 60, 'test');

            $value = Redis::connection()->get($testKey);
            if ($value !== 'test') {
                $this->error('Redis read/write test failed.');

                return Command::FAILURE;
            }

            Redis::connection()->del($testKey);
            $this->info('Redis operations test successful.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Redis connection check failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
