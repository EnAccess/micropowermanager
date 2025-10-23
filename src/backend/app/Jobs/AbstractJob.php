<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\UserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

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
        if ($t instanceof \Throwable) {
            Log::error(static::class.' failed for company '.$this->companyId, [
                'message' => $t->getMessage(),
                'trace' => $t->getTraceAsString(),
            ]);
        }
    }

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
