<?php

declare(strict_types=1);

namespace App\Plugins\SmsTransactionParser\Http\Controllers;

use App\Plugins\SmsTransactionParser\Http\Requests\SmsParsingRuleRequest;
use App\Plugins\SmsTransactionParser\Services\SmsParsingRuleService;
use App\Plugins\SmsTransactionParser\SmsParsing\TemplateToRegexConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class SmsParsingRuleController extends Controller {
    public function __construct(
        private SmsParsingRuleService $smsParsingRuleService,
        private TemplateToRegexConverter $templateToRegexConverter,
    ) {}

    public function index(): JsonResponse {
        return response()->json([
            'data' => $this->smsParsingRuleService->getAll(),
        ]);
    }

    public function store(SmsParsingRuleRequest $request): JsonResponse {
        $data = $request->validated();
        $data['pattern'] = $this->templateToRegexConverter->convert($data['template']);
        $rule = $this->smsParsingRuleService->create($data);

        return response()->json([
            'data' => $rule,
        ], 201);
    }

    public function update(int $id, SmsParsingRuleRequest $request): JsonResponse {
        $rule = $this->smsParsingRuleService->getById($id);
        $data = $request->validated();
        $data['pattern'] = $this->templateToRegexConverter->convert($data['template']);
        $updatedRule = $this->smsParsingRuleService->update($rule, $data);

        return response()->json([
            'data' => $updatedRule,
        ]);
    }

    public function destroy(int $id): JsonResponse {
        $rule = $this->smsParsingRuleService->getById($id);
        $this->smsParsingRuleService->delete($rule);

        return response()->json(null, 204);
    }

    public function install(): JsonResponse {
        $rules = $this->smsParsingRuleService->installDefaults();

        return response()->json([
            'data' => $rules,
        ]);
    }
}
