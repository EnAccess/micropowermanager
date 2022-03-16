<?php

namespace App\Listeners;

use App\Misc\SoldApplianceDataContainer;
use App\Models\AssetRate;
use App\Models\Person\Person;
use App\Models\Transaction\CashTransaction;
use App\Services\ApplianceRateService;
use Carbon\Carbon;
use Illuminate\Events\Dispatcher;

class SoldApplianceListener
{
    /**
     * @var AssetRate
     */
    private $assetRate;

    private $applianceRateService;

    public function __construct(AssetRate $assetRate, ApplianceRateService $applianceRateService)
    {
        $this->assetRate = $assetRate;
        $this->applianceRateService = $applianceRateService;
    }

    public function initializeApplianceRates(SoldApplianceDataContainer $soldAppliance): void
    {
        $assetPerson = $soldAppliance->getAssetPerson();
        $assetType = $soldAppliance->getAssetType();
        $transaction = $soldAppliance->getTransaction();
        $buyer = Person::query()->find($assetPerson->person_id);

        $this->applianceRateService->createApplianceRatesFromAssetPerson($assetPerson);

        if ($assetPerson->down_payment > 0) {
            event(
                'payment.successful',
                [
                    'amount' => $transaction->amount,
                    'paymentService' =>
                        $transaction->original_transaction_type === 'cash_transaction' ? 'web' : 'agent',
                    'paymentType' => 'down payment',
                    'sender' => $transaction->sender,
                    'paidFor' => $assetType,
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
