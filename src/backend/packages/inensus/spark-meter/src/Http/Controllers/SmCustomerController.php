<?php

namespace Inensus\SparkMeter\Http\Controllers;

use Illuminate\Http\Request;
use Inensus\SparkMeter\Http\Requests\SmCustomerRequest;
use Inensus\SparkMeter\Http\Resources\SparkResource;
use Inensus\SparkMeter\Models\SmCustomer;
use Inensus\SparkMeter\Services\CustomerService;

class SmCustomerController implements IBaseController {
    public function __construct(private CustomerService $customerService) {}

    public function index(Request $request): SparkResource {
        $customers = $this->customerService->getSmCustomers($request);

        return new SparkResource($customers);
    }

    public function sync(): SparkResource {
        return new SparkResource($this->customerService->sync());
    }

    public function checkSync(): SparkResource {
        return new SparkResource($this->customerService->syncCheck());
    }

    public function count(): int {
        return $this->customerService->getSmCustomersCount();
    }

    public function connection(): SparkResource {
        return new SparkResource($this->customerService->checkConnectionAvailability());
    }

    public function update(SmCustomer $customer, SmCustomerRequest $request): SparkResource {
        return new SparkResource($this->customerService->updateCustomerLowBalanceLimit($customer->id, $request->only([
            'low_balance_limit',
        ])));
    }

    public function search(): SparkResource {
        $term = request('term');
        $paginate = request('paginate') ?? 1;

        return new SparkResource($this->customerService->searchCustomer($term, $paginate));
    }
}
