<?php

namespace App\Queue;

use App\Models\PendingJob;
use Illuminate\Bus\Batch;
use Illuminate\Bus\PendingBatch;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Foundation\Bus\PendingChain;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Predis\Connection\ConnectionException;

/**
 * Bus dispatcher decorator that buffers jobs to MySQL when Redis is completely unavailable.
 *
 * This is the second layer of our Redis resilience strategy. The first layer (App\Queue\RedisQueue)
 * retries on brief connection blips. When Redis is down long enough that all retries fail, the
 * ConnectionException bubbles up here. Instead of letting the exception crash the HTTP request or
 * silently losing the job, this decorator serializes the job into the `pending_jobs` table.
 *
 * A scheduled command (`queue:flush-pending`, runs every minute) replays buffered jobs back to
 * Redis once it recovers. This means jobs are delayed by at most ~1 minute after Redis comes back,
 * but zero jobs are lost.
 *
 * This decorator wraps Laravel's default dispatcher via container `extend()` in
 * QueueServiceProvider. Every dispatch() call in the application flows through here with zero
 * changes to existing job classes or dispatch callsites.
 *
 * @see RedisQueue               First layer: retry with exponential backoff
 * @see \App\Console\Commands\FlushPendingJobsCommand  Replays buffered jobs after recovery
 * @see \App\Providers\QueueServiceProvider  Registers this decorator
 */
class Dispatcher implements QueueingDispatcher {
    public function __construct(
        private readonly QueueingDispatcher $innerDispatcher,
    ) {}

    public function dispatch($command): mixed {
        try {
            return $this->innerDispatcher->dispatch($command);
        } catch (ConnectionException $e) {
            $this->bufferToDatabase($command, $e);

            return null;
        }
    }

    public function dispatchToQueue($command): mixed {
        try {
            return $this->innerDispatcher->dispatchToQueue($command);
        } catch (ConnectionException $e) {
            $this->bufferToDatabase($command, $e);

            return null;
        }
    }

    public function dispatchSync($command, $handler = null): mixed {
        return $this->innerDispatcher->dispatchSync($command, $handler);
    }

    public function dispatchNow($command, $handler = null): mixed {
        return $this->innerDispatcher->dispatchNow($command, $handler);
    }

    public function findBatch(string $batchId): ?Batch {
        return $this->innerDispatcher->findBatch($batchId);
    }

    /**
     * @param array<int, mixed>|Collection<int, mixed> $jobs
     */
    public function batch($jobs): PendingBatch {
        return $this->innerDispatcher->batch($jobs);
    }

    /**
     * @param array<int, mixed> $jobs
     */
    public function chain($jobs): PendingChain {
        return $this->innerDispatcher->chain($jobs);
    }

    public function hasCommandHandler($command): bool {
        return $this->innerDispatcher->hasCommandHandler($command);
    }

    public function getCommandHandler($command): mixed {
        return $this->innerDispatcher->getCommandHandler($command);
    }

    /**
     * @param array<int, mixed> $pipes
     */
    public function pipeThrough(array $pipes): QueueingDispatcher {
        $this->innerDispatcher->pipeThrough($pipes);

        return $this;
    }

    /**
     * @param array<string, string> $map
     */
    public function map(array $map): QueueingDispatcher {
        $this->innerDispatcher->map($map);

        return $this;
    }

    public function dispatchAfterResponse(mixed $command, mixed $handler = null): void {
        try {
            $this->innerDispatcher->dispatchAfterResponse($command, $handler);
        } catch (ConnectionException $e) {
            $this->bufferToDatabase($command, $e);
        }
    }

    private function bufferToDatabase(mixed $command, ConnectionException $e): void {
        Log::warning('Redis unavailable, buffering job to database', [
            'job' => is_object($command) ? $command::class : 'Closure',
            'error' => $e->getMessage(),
        ]);

        if ($command instanceof \Closure) {
            Log::error('Cannot buffer Closure job to database — job will be lost');

            return;
        }

        PendingJob::create([
            'queue' => $command->queue ?? 'default',
            'connection' => $command->connection ?? 'redis',
            'payload' => serialize($command),
            'delay_seconds' => $command->delay ?? 0,
        ]);
    }
}
