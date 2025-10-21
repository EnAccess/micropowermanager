<?php

namespace Inensus\SparkMeter\Http\Controllers;

use Illuminate\Http\Request;
use Inensus\SparkMeter\Http\Resources\SparkResource;
use Inensus\SparkMeter\Services\SmSalesAccoutService;

class SmSalesAccountController implements IBaseController {
    public function __construct(private SmSalesAccoutService $smSalesAccountService) {}

    public function index(Request $request): SparkResource {
        $salesAccounts = $this->smSalesAccountService->getSmSalesAccounts($request);

        return new SparkResource($salesAccounts);
    }

    public function sync(): SparkResource {
        return new SparkResource($this->smSalesAccountService->sync());
    }

    public function checkSync(): SparkResource {
        return new SparkResource($this->smSalesAccountService->syncCheck());
    }

    public function count(): int {
        return $this->smSalesAccountService->getSmSalesAccountsCount();
    }
}
