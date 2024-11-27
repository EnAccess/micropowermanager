<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\RegistrationTail;
use App\Services\RegistrationTailService;
use Illuminate\Http\Request;

class RegistrationTailController extends Controller {
    public function __construct(private RegistrationTailService $registrationTailService) {}

    public function index(): ApiResource {
        return ApiResource::make($this->registrationTailService->getAll());
    }

    public function update(RegistrationTail $registrationTail, Request $request): ApiResource {
        $tail = $request->input('tail');
        $registrationTailData = [
            'tail' => $tail,
            'updated_by' => auth('api')->user()->id,
        ];
        $this->registrationTailService->update($registrationTail, $registrationTailData);

        return ApiResource::make($this->registrationTailService->getAll());
    }
}
