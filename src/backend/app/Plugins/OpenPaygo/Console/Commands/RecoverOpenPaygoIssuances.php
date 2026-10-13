<?php

namespace App\Plugins\OpenPaygo\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Plugins\OpenPaygo\Services\OpenPaygoIssuanceService;

class RecoverOpenPaygoIssuances extends AbstractSharedCommand {
    protected $signature = 'openpaygo:recover-issuances {--stale-after=10}';

    protected $description = 'Recover safe stale OpenPAYGO issuance reservations and mark uncertain requests';

    public function handle(OpenPaygoIssuanceService $issuanceService): int {
        $recovered = $issuanceService->recoverStaleReservations((int) $this->option('stale-after'));
        $this->info("Processed {$recovered} OpenPAYGO reservations; uncertain requests remain blocked.");

        return self::SUCCESS;
    }
}
