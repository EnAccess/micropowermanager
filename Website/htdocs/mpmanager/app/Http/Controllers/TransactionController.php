<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Jobs\ProcessPayment;
use Illuminate\Http\Request;
use MPM\Transaction\Provider\ITransactionProvider;
use MPM\Transaction\TransactionService;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionService $transactionService,
    ) {

    }
    public function index(): ApiResource
    {
        $limit = \request()->get('per_page') ?? 15;
        return ApiResource::make($this->transactionService->getAll($limit));
    }

    public function show(int $id): ApiResource
    {
        $transaction = $this->transactionService->getById($id);
        return ApiResource::make($transaction);
    }

    public function store(Request $request): void
    {
        /**
         * @var ITransactionProvider
         */
        $transactionProvider = $request->attributes->get('transactionProcessor');
        // save main transaction
        $transactionProvider->saveTransaction();
        // store common data
        $transaction = $transactionProvider->saveCommonData();
        //fire transaction.saved -> confirms the transaction
        event('transaction.saved', $transactionProvider);

        ProcessPayment::dispatch($transaction->id)
            ->allOnConnection('redis')
            ->onQueue(config('services.queues.payment'));
    }

}
