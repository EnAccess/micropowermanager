<?php

namespace Inensus\SteamaMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\SteamaMeter\Http\Requests\SteamaCustomerRequest;
use Inensus\SteamaMeter\Http\Resources\SteamaResource;
use Inensus\SteamaMeter\Models\SteamaCustomer;
use Inensus\SteamaMeter\Services\SteamaCustomerService;

class SteamaCustomerController extends Controller implements IBaseController {
    private $customerService;

    public function __construct(SteamaCustomerService $steamaCustomerService) {
        $this->customerService = $steamaCustomerService;
    }

    public function index(Request $request): SteamaResource {
        $customers = $this->customerService->getCustomers($request);

        return new SteamaResource($customers);
    }

    public function get($customerId): SteamaResource {
        return new SteamaResource($this->customerService->getSteamaCustomerName($customerId));
    }

    public function sync(): SteamaResource {
        return new SteamaResource($this->customerService->sync());
    }

    public function checkSync(): SteamaResource {
        return new SteamaResource($this->customerService->syncCheck());
    }

    public function count() {
        return $this->customerService->getCustomersCount();
    }

    public function update(SteamaCustomer $stmCustomer, SteamaCustomerRequest $request): SteamaResource {
        $customerData = [
            'id' => $stmCustomer->customer_id,
            'low_balance_warning' => $request->input('low_balance_warning'),
            'energy_price' => $request->input('energy_price'),
        ];

        return new SteamaResource($this->customerService->updateSteamaCustomerInfo($stmCustomer, $customerData));
    }

    public function search(): SteamaResource {
        $term = request('term');
        $paginate = request('paginate') ?? 1;

        return new SteamaResource($this->customerService->searchCustomer($term, $paginate));
    }
}
