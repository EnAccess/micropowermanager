<?php

namespace Inensus\EcreeeETender\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inensus\EcreeeETender\Services\EcreeeMeterDataService;
class EcreeeMeterDataController extends Controller {
    public function __construct(
        private EcreeeMeterDataService $ecreeeMeterDataService,
    ) {}

    public function index(Request $request): JsonResponse {
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        if (!$startDate || !$endDate) {
            return response()->json(['data' => [], 'errors' => 'startDate and endDate are required'], 400);
        }
        
        $startDate = CarbonImmutable::parse($startDate);
        $endDate = CarbonImmutable::parse($endDate);
        
        if ($startDate->isAfter($endDate)) {
            return response()->json(['data' => [], 'errors' => 'startDate must be before endDate'], 400);
        }
        
        $hoursDiff = $startDate->diffInHours($endDate);        
        if ($hoursDiff > 24) {
            return response()->json(['data' => [], 'errors' => 'Range must be <= 24 hours'], 400);
        }

        $meterData = $this->ecreeeMeterDataService->getMeterData($startDate, $endDate);

        return response()->json(['data' => $meterData]);
    }
}
