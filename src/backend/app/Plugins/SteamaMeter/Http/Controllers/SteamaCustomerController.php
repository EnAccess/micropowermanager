<?php

namespace App\Plugins\SteamaMeter\Http\Controllers;

use App\Plugins\SteamaMeter\Http\Requests\SteamaCustomerRequest;
use App\Plugins\SteamaMeter\Http\Resources\SteamaResource;
use App\Plugins\SteamaMeter\Models\SteamaCustomer;
use App\Plugins\SteamaMeter\Services\SteamaCustomerService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

#[Group('Plugins / Steama Meter')]
class SteamaCustomerController extends Controller implements IBaseController {
    public function __construct(private SteamaCustomerService $customerService) {}

    public function index(Request $request): SteamaResource {
        $customers = $this->customerService->getCustomers($request);

        return new SteamaResource($customers);
    }

    public function get(int $customerId): SteamaResource {
        return new SteamaResource($this->customerService->getSteamaCustomerName($customerId));
    }

    public function sync(): SteamaResource {
        return new SteamaResource($this->customerService->sync());
    }

    public function checkSync(): SteamaResource {
        return new SteamaResource($this->customerService->syncCheck());
    }

    public function count(): int {
        return $this->customerService->getCustomersCount();
    }

    public function update(SteamaCustomer $customer, SteamaCustomerRequest $request): SteamaResource {
        $customerData = [
            'id' => $customer->customer_id,
            'low_balance_warning' => $request->float('low_balance_warning'),
            'energy_price' => $request->float('energy_price'),
        ];

        return new SteamaResource($this->customerService->updateSteamaCustomerInfo($customer, $customerData));
    }

    public function search(): SteamaResource {
        $term = request('term');
        $paginate = request('paginate') ?? 1;

        return new SteamaResource($this->customerService->searchCustomer($term, $paginate));
    }
}
