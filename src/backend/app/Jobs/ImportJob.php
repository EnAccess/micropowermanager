<?php

namespace App\Jobs;

use App\Exceptions\ImportFailedException;
use Illuminate\Support\Facades\Cache;

class ImportJob extends AbstractJob {
    private const int CACHE_TTL_SECONDS = 3600;

    /**
     * @param array<int|string, mixed> $data
     */
    public function __construct(
        int $companyId,
        protected string $jobId,
        protected string $importServiceClass,
        protected array $data,
    ) {
        $this->onConnection('redis');
        $this->onQueue('import');
        parent::__construct($companyId);
    }

    public function executeJob(): void {
        $cacheKey = $this->cacheKey();

        $this->updateCache($cacheKey, ['status' => 'processing']);

        $service = app()->make($this->importServiceClass);

        try {
            $result = $service->import($this->data);

            $this->updateCache($cacheKey, [
                'status' => 'completed',
                'result' => $result->toArray(),
                'completed_at' => now()->toISOString(),
            ]);
        } catch (ImportFailedException $exception) {
            $this->updateCache($cacheKey, [
                'status' => 'failed',
                'result' => ['success' => false, 'errors' => $exception->errors()],
                'completed_at' => now()->toISOString(),
            ]);
        }
    }

    public function failed(?\Throwable $t = null): void {
        parent::failed($t);

        $cacheKey = $this->cacheKey();
        $errorMessage = $t instanceof \Throwable ? $t->getMessage() : 'Unknown error';

        $this->updateCache($cacheKey, [
            'status' => 'failed',
            'error' => $errorMessage,
            'completed_at' => now()->toISOString(),
        ]);
    }

    private function cacheKey(): string {
        return "import:{$this->companyId}:{$this->jobId}";
    }

    /**
     * @param array<string, mixed> $updates
     */
    private function updateCache(string $key, array $updates): void {
        $data = Cache::get($key, []);
        $data = array_merge($data, $updates);
        Cache::put($key, $data, self::CACHE_TTL_SECONDS);
    }
}
