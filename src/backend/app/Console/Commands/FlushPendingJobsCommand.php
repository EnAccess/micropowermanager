<?php

namespace App\Console\Commands;

use App\Models\PendingJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Predis\Connection\ConnectionException;

class FlushPendingJobsCommand extends Command {
    protected $signature = 'queue:flush-pending {--batch=50}';

    protected $description = 'Replay buffered pending jobs from the database back to Redis';

    private const MAX_ATTEMPTS = 10;

    private const TIME_LIMIT_SECONDS = 30;

    public function handle(): int {
        if (!$this->isRedisAvailable()) {
            $this->info('Redis is not available yet — skipping flush.');

            return self::SUCCESS;
        }

        $batchSize = (int) $this->option('batch');
        $startTime = time();
        $totalFlushed = 0;

        while (true) {
            $pendingJobs = PendingJob::query()
                ->orderBy('id')
                ->limit($batchSize)
                ->lockForUpdate()
                ->get();

            if ($pendingJobs->isEmpty()) {
                break;
            }

            $batchFlushed = 0;

            foreach ($pendingJobs as $pendingJob) {
                if ($this->processJob($pendingJob)) {
                    ++$batchFlushed;
                    ++$totalFlushed;
                }
            }

            // No progress in this batch — remaining jobs are all failing, stop to avoid infinite loop
            if ($batchFlushed === 0) {
                break;
            }

            if ((time() - $startTime) >= self::TIME_LIMIT_SECONDS) {
                $this->info("Time limit reached after flushing {$totalFlushed} jobs.");

                break;
            }
        }

        if ($totalFlushed > 0) {
            $this->info("Flushed {$totalFlushed} pending jobs to Redis.");
            Log::info('Flushed pending jobs to Redis', ['count' => $totalFlushed]);
        }

        return self::SUCCESS;
    }

    /**
     * @return bool Whether the job was successfully dispatched
     */
    private function processJob(PendingJob $pendingJob): bool {
        try {
            $command = unserialize($pendingJob->payload);

            if ($command === false) {
                throw new \RuntimeException('Failed to unserialize pending job payload');
            }

            dispatch($command);
            $pendingJob->delete();

            return true;
        } catch (ConnectionException $e) {
            Log::warning('Redis became unavailable during flush — stopping', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (\Throwable $e) {
            $pendingJob->increment('attempts');

            if ($pendingJob->attempts >= self::MAX_ATTEMPTS) {
                Log::critical('Pending job exceeded max attempts and will be discarded', [
                    'id' => $pendingJob->id,
                    'queue' => $pendingJob->queue,
                    'error' => $e->getMessage(),
                ]);

                $pendingJob->delete();

                return false;
            }

            Log::warning('Failed to flush pending job', [
                'id' => $pendingJob->id,
                'attempt' => $pendingJob->attempts,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function isRedisAvailable(): bool {
        try {
            Redis::connection()->ping();

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
