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
        $this->registrationTailService->update($registrationTail, [
            'adjusted' => $request->boolean('adjusted', true),
            'updated_by' => auth('api')->user()->id,
        ]);

        return ApiResource::make($this->registrationTailService->getAll());
    }
}
