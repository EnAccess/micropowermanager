<?php

namespace Inensus\SteamaMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\SteamaMeter\Http\Resources\SteamaResource;
use Inensus\SteamaMeter\Services\SteamaTransactionsService;

class SteamaTransactionController extends Controller {
    public function __construct(private SteamaTransactionsService $steamaTransactionsService) {}

    public function index($customer, Request $request): SteamaResource {
        return new SteamaResource($this->steamaTransactionsService->getTransactionsByCustomer($customer, $request));
    }
}
