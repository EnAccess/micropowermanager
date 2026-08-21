<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\OperatorDashboardRebuildJob;
use App\Services\OperatorDashboardService;
use Illuminate\Console\Command;

/**
 * Rebuilds the operator dashboard aggregate.
 *
 * It does not extend {@see AbstractSharedCommand}: that base is built for
 * per-tenant work — it returns its execution type as the process exit code (so a
 * single-company run reports failure), gives no hook to run once after all
 * tenants, and aborts the remaining tenants on the first error. This command
 * needs the opposite of all three.
 */
class OperatorDashboardRebuild extends Command {
    protected $signature = 'operator-dashboard:rebuild {--company-id=} {--sync}';
    protected $description = 'Rebuild the cached operator dashboard aggregate across all tenants';

    public function handle(OperatorDashboardService $operatorDashboardService): int {
        $companyId = $this->option('company-id');
        $companyId = $companyId === null ? null : (int) $companyId;

        if ($this->option('sync')) {
            $operatorDashboardService->rebuild($companyId);
            $operatorDashboardService->clearRefreshing();
            $this->info('Operator dashboard rebuilt.');

            return self::SUCCESS;
        }

        $operatorDashboardService->markRefreshing();
        dispatch(new OperatorDashboardRebuildJob($companyId));
        $this->info('Operator dashboard rebuild queued.');

        return self::SUCCESS;
    }
}
