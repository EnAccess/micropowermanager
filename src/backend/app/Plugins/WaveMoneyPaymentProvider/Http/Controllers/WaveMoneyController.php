<?php

declare(strict_types=1);

namespace App\Plugins\WaveMoneyPaymentProvider\Http\Controllers;

use App\Plugins\WaveMoneyPaymentProvider\Http\Requests\TransactionInitializeRequest;
use App\Plugins\WaveMoneyPaymentProvider\Http\Resources\WaveMoneyResource;
use App\Plugins\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use App\Plugins\WaveMoneyPaymentProvider\Modules\Api\Data\TransactionCallbackData;
use App\Plugins\WaveMoneyPaymentProvider\Modules\Api\WaveMoneyApiService;
use App\Plugins\WaveMoneyPaymentProvider\Modules\Transaction\WaveMoneyTransactionService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

#[Group('Plugins / Wave Money', 'API endpoints for integrating with Wave Money payment services')]
class WaveMoneyController extends Controller {
    public function __construct(
        private WaveMoneyApiService $apiService,
        private WaveMoneyTransactionService $transactionService,
    ) {}

    public function startTransaction(TransactionInitializeRequest $request): WaveMoneyResource {
        // WaveMoneyTransactionMiddleware resolves this onto the request attributes; it is not
        // request input, so it cannot be read with input()/string().
        /** @var WaveMoneyTransaction $transaction */
        $transaction = $request->attributes->get('waveMoneyTransaction');

        return WaveMoneyResource::make($this->apiService->requestPayment($transaction));
    }

    public function transactionCallBack(Request $request): void {
        /** @var WaveMoneyTransaction $waveMoneyTransaction */
        $waveMoneyTransaction = $request->attributes->get('waveMoneyTransaction');
        /** @var TransactionCallbackData $callbackData */
        $callbackData = $request->attributes->get('callbackData');

        $this->transactionService->applyCallback(
            $waveMoneyTransaction,
            $callbackData,
            $request->attributes->getInt('companyId'),
        );
    }
}
