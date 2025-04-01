<?php

namespace App\Console\Commands;

use App\Models\AccessRate\AccessRatePayment;
use Carbon\Carbon;

class AccessRateChecker extends AbstractSharedCommand {
    protected $signature = 'accessrate:check';
    protected $description = 'Updates the "debt" field, based on "due_date" field';

    public function handle(): void {
        // get all access-rate payments where due Date is <= today
        $accessRatePayments = AccessRatePayment::where('due_date', '<=', Carbon::now())->get();

        // iterate in unpaid acess-rates
        foreach ($accessRatePayments as $accessRatePayment) {
            $accessRate = $accessRatePayment->accessRate()->first();
            if ($accessRate === null) {
                continue;
            }
            $accessRatePayment->due_date = Carbon::now()->addDays($accessRate->period);
            // access-rate is defined
            if ($accessRate->amount > 0) {
                $accessRatePayment->debt += $accessRate->amount;
                ++$accessRatePayment->unpaid_in_row;
            }
            $accessRatePayment->save();

            if ($accessRatePayment->unpaid_in_row > 1) {
                // unpaid in row = 2 notify call-center && send reminder to customer
                // unpaid =3 notify call-center && send warning to customer
                // unpaid =4 cutoff electricity
                // unpaid = 1 send customer a reminder
            }
        }
    }
}
