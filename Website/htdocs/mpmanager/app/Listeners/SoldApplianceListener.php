<?php

namespace App\Listeners;

use App\Misc\SoldApplianceDataContainer;
use App\Models\AssetRate;
use App\Models\Person\Person;
use App\Models\Transaction\CashTransaction;
use App\Services\ApplianceRateService;
use App\Services\PersonService;
use Carbon\Carbon;
use Illuminate\Events\Dispatcher;

class SoldApplianceListener
{
    public function __construct(
        private ApplianceRateService $applianceRateService,
        private PersonService $personService
    ) {
    }

    public function initializeApplianceRates(SoldApplianceDataContainer $soldAppliance): void
    {
        $assetPerson = $soldAppliance->getAssetPerson();
        $assetType = $soldAppliance->getAssetType();
        $transaction = $soldAppliance->getTransaction();
        $asset = $soldAppliance->getAsset();
        $buyer = $this->personService->getById($assetPerson->person_id);

        $this->applianceRateService->create($assetPerson);

        if ($assetPerson->down_payment > 0) {
            event(
                'payment.successful',
                [
                    'amount' => $transaction->amount,
                    'paymentService' =>
                        $transaction->original_transaction_type === 'cash_transaction' ? 'web' : 'agent',
                    'paymentType' => 'down payment',
                    'sender' => $transaction->sender,
                    'paidFor' => $asset,
                    'payer' => $buyer,
                    'transaction' => $transaction,
                ]
            );
        }
    }


    public function subscribe(Dispatcher $events): void
    {
        $events->listen('appliance.sold', 'App\Listeners\SoldApplianceListener@initializeApplianceRates');
    }
}
