<?php

namespace Inensus\SteamaMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\SteamaMeter\Http\Resources\SteamaResource;
use Inensus\SteamaMeter\Services\SteamaTransactionsService;

class SteamaTransactionController extends Controller {
    private $steamaTransactionsService;

    public function __construct(SteamaTransactionsService $steamaTransactionsService) {
        $this->steamaTransactionsService = $steamaTransactionsService;
    }

    public function index($customer, Request $request): SteamaResource {
        return new SteamaResource($this->steamaTransactionsService->getTransactionsByCustomer($customer, $request));
    }
}
