<?php

namespace Inensus\EcreeeETender\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MPM\Ecreee\EcreeeMeterDataService;
use MPM\Ecreee\EcreeeTokenService;

class EcreeeMeterDataController extends Controller {
    public function __construct(
        private EcreeeMeterDataService $ecreeeMeterDataService,
        private EcreeeTokenService $tokenService,
    ) {}

    public function index(Request $request) {
        $token = $request->query('token');
        $ecreeeToken = $this->tokenService->getByToken($token);

        if (!$ecreeeToken || (bool) $ecreeeToken->is_active === false) {
            return response()->json(['message' => 'Invalid token'], 404);
        }

        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        return $this->ecreeeMeterDataService->getMeterData($startDate, $endDate);
    }
}
