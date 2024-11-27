<?php

declare(strict_types=1);

namespace Inensus\WavecomPaymentProvider\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use Inensus\WavecomPaymentProvider\Http\Requests\UploadTransactionRequest;
use Inensus\WavecomPaymentProvider\Services\TransactionService;

class WaveComTransactionController extends Controller {
    public function __construct(private TransactionService $transactionService) {}

    public function uploadTransaction(UploadTransactionRequest $request): ApiResource {
        $file = $request->getFile();
        $result = $this->transactionService->createTransactionsFromFile($file);

        $response['result'] = 'success';
        if (\count($result) > 0) {
            $response['reason'] = $result;
        }

        return ApiResource::make($response);
    }
}
