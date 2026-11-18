<?php

namespace App\Plugins\OpenPaygo\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Plugins\OpenPaygo\Exceptions\OpenPaygoIssuanceException;
use App\Plugins\OpenPaygo\Services\OpenPaygoIssuanceService;

class RecoverOpenPaygoIssuances extends AbstractSharedCommand {
    protected $signature = 'openpaygo:recover-issuances {--stale-after=10} {--reservation-id=} {--token=} {--next-counter=}';

    protected $description = 'Recover safe stale OpenPAYGO issuance reservations and mark uncertain requests';

    public function handle(OpenPaygoIssuanceService $issuanceService): int {
        if ($this->option('reservation-id') !== null || $this->option('token') !== null || $this->option('next-counter') !== null) {
            if (!ctype_digit((string) $this->option('company-id')) || (int) $this->option('company-id') < 1
                || !ctype_digit((string) $this->option('reservation-id'))
                || !ctype_digit((string) $this->option('next-counter'))
                || !is_string($this->option('token'))) {
                throw new OpenPaygoIssuanceException('Reconciliation requires --company-id, --reservation-id, --token and --next-counter.');
            }
            $issuanceService->reconcileUncertainReservation(
                (int) $this->option('reservation-id'),
                $this->option('token'),
                (int) $this->option('next-counter'),
            );
            $this->info('Verified OpenPAYGO generator result reconciled.');
        }

        $recovered = $issuanceService->recoverStaleReservations((int) $this->option('stale-after'));
        $this->info("Processed {$recovered} OpenPAYGO reservations; uncertain requests remain blocked.");

        return self::SUCCESS;
    }
}
