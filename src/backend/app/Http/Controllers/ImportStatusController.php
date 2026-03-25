<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ImportStatusController extends Controller {
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
