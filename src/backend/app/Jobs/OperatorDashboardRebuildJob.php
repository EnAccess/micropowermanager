<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\OperatorDashboardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Rebuilds the operator dashboard aggregate off the request cycle.
 *
 * It deliberately does not extend {@see AbstractJob}: that base binds a single
 * tenant connection around the work and defaults its company from the
 * authenticated user, whereas this job spans every tenant and runs for an
 * operator who is not a tenant user at all.
 */
class OperatorDashboardRebuildJob implements ShouldQueue {
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800;

    // Retrying would walk every tenant database a second time; a failed rebuild
    // leaves the previous snapshots in place, so the dashboard stays usable.
    public int $tries = 1;

    public function __construct(private ?int $companyId = null) {
        $this->onConnection('redis');
        $this->onQueue('operator_dashboard');
    }

    public function handle(OperatorDashboardService $operatorDashboardService): void {
        try {
            $operatorDashboardService->rebuild($this->companyId);
        } finally {
            $operatorDashboardService->clearRefreshing();
        }
    }

    public function failed(?\Throwable $throwable = null): void {
        Log::error('Operator dashboard rebuild failed', [
            'companyId' => $this->companyId,
            'message' => $throwable?->getMessage(),
        ]);

        app()->make(OperatorDashboardService::class)->clearRefreshing();
    }
}
