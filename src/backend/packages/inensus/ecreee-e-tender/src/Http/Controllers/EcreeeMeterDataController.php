<?php

namespace Inensus\EcreeeETender\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\EcreeeETender\Services\EcreeeMeterDataService;
use Inensus\EcreeeETender\Services\EcreeeTokenService;

class EcreeeMeterDataController extends Controller {
    public function __construct(
        private EcreeeMeterDataService $ecreeeMeterDataService,
        private EcreeeTokenService $tokenService,
    ) {}

    public function index(Request $request): JsonResponse {
        $token = $request->query('token');
        $ecreeeToken = $this->tokenService->getByToken($token);

        if (!$ecreeeToken || (bool) $ecreeeToken->is_active === false) {
            return response()->json(['message' => 'Invalid token'], 404);
        }

        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        $meterData = $this->ecreeeMeterDataService->getMeterData($startDate, $endDate);
        return response()->json(['data' => $meterData]);
    }
}
