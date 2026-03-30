<?php

declare(strict_types=1);

namespace App\Plugins\WavecomPaymentProvider\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Plugins\WavecomPaymentProvider\Http\Requests\UploadTransactionRequest;
use App\Plugins\WavecomPaymentProvider\Services\TransactionService;

class WaveComTransactionController extends Controller {
    public function __construct(private TransactionService $transactionService) {}

    public function uploadTransaction(UploadTransactionRequest $request): ApiResource {
        $file = $request->getFile();
        $companyId = $request->attributes->get('companyId') ?? null;
        $result = $this->transactionService->createTransactionsFromFile($file, $companyId);

        $response['result'] = 'success';
        if (\count($result) > 0) {
            $response['reason'] = $result;
        }

        return ApiResource::make($response);
    }
}
