<?php

namespace Inensus\SparkMeter\Http\Controllers;

use Illuminate\Http\Request;
use Inensus\SparkMeter\Http\Resources\SparkResource;
use Inensus\SparkMeter\Services\SmSalesAccoutService;

class SmSalesAccountController implements IBaseController {
    private $smSalesAccountService;

    public function __construct(SmSalesAccoutService $salesAccountService) {
        $this->smSalesAccountService = $salesAccountService;
    }

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

    public function count() {
        return $this->smSalesAccountService->getSmSalesAccountsCount();
    }
}
