<?php

namespace App\Console\Commands;

use App\Services\ApplianceRateService;
use Carbon\CarbonImmutable;
use MPM\OutstandingDebts\OutstandingDebtsExportService;

class MailApplianceDebtsCommand extends AbstractSharedCommand {
    protected $signature = 'mail:appliance-debts';

    protected $description = 'Send mail to customers with appliance debts';

    public function handle(ApplianceRateService $applianceRateService, OutstandingDebtsExportService $outstandingDebtsExportService): void {
        $toDate = CarbonImmutable::now();
        $applianceDebtHavingCustomerCount = $applianceRateService->queryOutstandingDebtsByApplianceRates($toDate)->count();
        // do not send mail if there is no customer with appliance debt
        if ($applianceDebtHavingCustomerCount > 0) {
            $outstandingDebtsExportService->sendApplianceDebtsAsEmail();
        }
    }
}
