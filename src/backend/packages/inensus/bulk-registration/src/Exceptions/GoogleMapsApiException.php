<?php

namespace Inensus\BulkRegistration\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleMapsApiException extends \Exception {
    public function render(Request $request): JsonResponse {
        return response()->json([
            'errors' => [
                'code' => 422,
                'title' => 'Google Maps API Error',
                'message' => json_decode($this->getMessage()),
                'meta' => '',
            ],
        ], 400);
    }
}
