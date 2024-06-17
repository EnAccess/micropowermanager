<?php

namespace app\Console\Commands;

use App\Services\ApplianceRateService;
use MPM\OutstandingDebts\OutstandingDebtsExportService;

class MailApplianceDebtsCommand extends AbstractSharedCommand
{
    protected $signature = 'mail:appliance-debts';

    protected $description = 'Send mail to customers with appliance debts';

    public function handle(ApplianceRateService $applianceRateService, OutstandingDebtsExportService $outstandingDebtsExportService): void
    {
        $applianceDebtHavingCustomerCount = $applianceRateService->queryOutstandingDebtsByApplianceRates()->count();
        // do not send mail if there is no customer with appliance debt
        if ($applianceDebtHavingCustomerCount > 0) {
            $outstandingDebtsExportService->sendApplianceDebtsAsEmail();
        }
    }
}
