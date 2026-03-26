<?php

namespace App\Queue;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Queue\RedisQueue as BaseRedisQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Predis\Connection\ConnectionException;

/**
 * Resilient Redis queue that retries on transient connection failures.
 *
 * Redis runs on Kubernetes and gets restarted occasionally (rolling deployments, OOM kills, node
 * evictions). Laravel's default RedisQueue treats any ConnectionException as fatal — a 1-second
 * blip kills the Horizon worker or loses a dispatched job. This subclass wraps every Redis
 * operation (push, pop, later) in a retry loop with exponential backoff so that brief outages
 * (1-3s) are absorbed transparently without any job loss or worker crashes.
 *
 * If all retries are exhausted the ConnectionException propagates up to the Dispatcher (see
 * App\Queue\Dispatcher), which catches it and buffers the job to MySQL as a last resort.
 *
 * Retry behaviour is configured via queue.php: `retry_connection_attempts` and
 * `retry_connection_delay_ms`.
 *
 * @see Dispatcher  DB fallback when retries are exhausted
 * @see RedisConnector  Wires this class in place of Laravel's RedisQueue
 */
class RedisQueue extends BaseRedisQueue {
    /**
     * @var array<int, int>
     */
    protected array $retryBackoffMs = [
        1000,
        5000,
        10000,
        60000,
    ];

    public function __construct(
        Factory $redis,
        $default = 'default',
        $connection = null,
        $retryAfter = 60,
        $blockFor = null,
    ) {
        parent::__construct($redis, $default, $connection, $retryAfter, $blockFor);
    }

    public function push($job, $data = '', $queue = null): mixed {
        return $this->retryOnConnectionFailure(fn () => parent::push($job, $data, $queue));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function pushRaw($payload, $queue = null, array $options = []): mixed {
        return $this->retryOnConnectionFailure(fn () => parent::pushRaw($payload, $queue, $options));
    }

    public function later($delay, $job, $data = '', $queue = null): mixed {
        return $this->retryOnConnectionFailure(fn () => parent::later($delay, $job, $data, $queue));
    }

    public function pop($queue = null, mixed $index = 0): ?Job {
        return $this->retryOnConnectionFailure(fn () => parent::pop($queue, $index));
    }

    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     *
     * @throws ConnectionException
     */
    private function retryOnConnectionFailure(callable $callback): mixed {
        $lastException = null;

        foreach ($this->retryBackoffMs as $attempt => $delayMs) {
            try {
                return $callback();
            } catch (ConnectionException $e) {
                $lastException = $e;

                Log::warning('Redis connection failed, retrying', [
                    'attempt' => $attempt + 1,
                    'max_attempts' => count($this->retryBackoffMs),
                    'retry_in_ms' => $delayMs,
                    'error' => $e->getMessage(),
                ]);
                // add a small time difference between retries
                $delayMs += random_int(0, 500);
                Sleep::usleep($delayMs * 1000);
            }
        }

        throw $lastException;
    }
}
