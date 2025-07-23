<?php

namespace App\Http\Controllers;

use App\Http\Requests\AndroidAppRequest;
use App\Http\Resources\ApiResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MPM\Apps\CustomerRegistration\CustomerRegistrationAppService;

class CustomerRegistrationAppController extends Controller {
    public function __construct(private CustomerRegistrationAppService $customerRegistrationAppService) {}

    public function store(AndroidAppRequest $request): JsonResponse {
        try {
            DB::connection('tenant')->beginTransaction();
            $person = $this->customerRegistrationAppService->createCustomer($request);
            DB::connection('tenant')->commit();

            return ApiResource::make($person)->response()->setStatusCode(201);
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::critical('Error while adding new Customer', ['message' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }
}
