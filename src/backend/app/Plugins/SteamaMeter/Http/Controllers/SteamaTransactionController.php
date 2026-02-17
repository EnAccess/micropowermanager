<?php

namespace App\Plugins\SteamaMeter\Http\Controllers;

use App\Plugins\SteamaMeter\Http\Resources\SteamaResource;
use App\Plugins\SteamaMeter\Models\SteamaCustomer;
use App\Plugins\SteamaMeter\Services\SteamaTransactionsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SteamaTransactionController extends Controller {
    public function __construct(
        private SteamaTransactionsService $steamaTransactionsService,
    ) {}

    public function index(SteamaCustomer $customer, Request $request): SteamaResource {
        return new SteamaResource($this->steamaTransactionsService->getTransactionsByCustomer($customer, $request));
    }
}
