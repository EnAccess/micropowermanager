<?php

declare(strict_types=1);

namespace App\Plugins\SmsTransactionParser\Http\Controllers;

use App\Plugins\SmsTransactionParser\Services\SmsParsingRuleService;
use App\Plugins\SmsTransactionParser\Services\SmsTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class SmsTransactionController extends Controller {
    public function __construct(
        private SmsTransactionService $smsTransactionService,
        private SmsParsingRuleService $smsParsingRuleService,
    ) {}

    public function index(): JsonResponse {
        return response()->json(
            $this->smsTransactionService->getAll(),
        );
    }

    public function byParsingRule(int $id): JsonResponse {
        $rule = $this->smsParsingRuleService->getById($id);

        return response()->json(
            $this->smsTransactionService->getByProviderName($rule->provider_name),
        );
    }
}
