<?php

namespace App\Plugins\SparkMeter\Http\Controllers;

use App\Plugins\SparkMeter\Http\Resources\SparkResource;
use App\Plugins\SparkMeter\Services\TransactionService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

#[Group('Plugins / Spark Meter')]
class SmTransactionController extends Controller {
    public function __construct(
        private TransactionService $transactionService,
    ) {}

    public function index(Request $request): SparkResource {
        return new SparkResource($this->transactionService->getTransactions($request));
    }

    public function sync(): SparkResource {
        $this->transactionService->sync();

        return new SparkResource($this->transactionService->getTransactions(request()));
    }
}
