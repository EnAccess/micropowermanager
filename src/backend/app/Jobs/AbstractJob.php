<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\DatabaseProxyManagerService;
use App\Services\UserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

abstract class AbstractJob implements ShouldQueue {
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected int $companyId;

    abstract public function executeJob(): void;

    public function __construct(?int $companyId = null) {
        $this->afterCommit = true;

        $this->companyId = $companyId ?? app()->make(UserService::class)->getCompanyId();
    }

    public function handle(): void {
        $databaseProxyManager = app()->make(DatabaseProxyManagerService::class);
        $databaseProxyManager->runForCompany($this->companyId, function (): void {
            $this->executeJob();
        });
    }

    public function failed(?\Throwable $t = null): void {
        if (!$t instanceof \Throwable) {
            return;
        }

        Log::error(static::class.' failed for company '.$this->companyId, [
            'message' => $t->getMessage(),
            'trace' => $t->getTraceAsString(),
        ]);

        try {
            app()->make(DatabaseProxyManagerService::class)
                ->runForCompany($this->companyId, function () use ($t): void {
                    $this->handleFailure($t);
                });
        } catch (\Throwable $throwable) {
            Log::error(static::class.' could not record its failure for company '.$this->companyId, [
                'message' => $throwable->getMessage(),
            ]);
        }
    }

    /**
     * Runs inside the tenant database context, which `failed()` itself does not
     * establish. Override to persist the outcome of a job that gave up.
     */
    protected function handleFailure(\Throwable $throwable): void {}

    /**
     * Dispatch the job for all tenants.
     *
     * @param mixed ...$args
     */
    public static function dispatchForAllTenants(...$args): void {
        foreach (Company::pluck('id') as $companyId) {
            dispatch(new (static::class)($companyId, ...$args));
        }
    }
}
