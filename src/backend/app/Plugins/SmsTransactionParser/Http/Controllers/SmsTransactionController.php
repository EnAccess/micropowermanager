<?php

declare(strict_types=1);

namespace App\Plugins\SmsTransactionParser\Http\Controllers;

use App\Plugins\SmsTransactionParser\Services\SmsTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class SmsTransactionController extends Controller {
    public function __construct(
        private SmsTransactionService $smsTransactionService,
    ) {}

    public function index(): JsonResponse {
        return response()->json(
            $this->smsTransactionService->getAll(),
        );
    }
}
