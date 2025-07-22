<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\SmsResendInformationKey;
use App\Services\SmsResendInformationKeyService;
use Illuminate\Http\Request;

class SmsResendInformationKeyController extends Controller {
    private SmsResendInformationKeyService $smsResendInformationKeyService;

    public function __construct(SmsResendInformationKeyService $smsResendInformationKeyService) {
        $this->smsResendInformationKeyService = $smsResendInformationKeyService;
    }

    public function index(): ApiResource {
        return new ApiResource($this->smsResendInformationKeyService->getResendInformationKeys());
    }

    public function update(SmsResendInformationKey $smsResendInformationKey, Request $request): ApiResource {
        return new ApiResource($this->smsResendInformationKeyService->updateResendInformationKey(
            $smsResendInformationKey,
            $request->all()
        ));
    }
}
