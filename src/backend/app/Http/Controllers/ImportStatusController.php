<?php

namespace App\Http\Controllers;

use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

#[Group('Import', 'Import data from a MicroPowerManager JSON export.', weight: 10)]
class ImportStatusController extends Controller {
    /**
     * Get the status of a queued import job.
     */
    public function show(string $jobId, Request $request): JsonResponse {
        $companyId = (int) $request->attributes->get('companyId');
        $cacheKey = "import:{$companyId}:{$jobId}";

        $status = Cache::get($cacheKey);

        if ($status === null) {
            return response()->json(['message' => 'Import job not found'], 404);
        }

        return response()->json(['data' => $status]);
    }
}
