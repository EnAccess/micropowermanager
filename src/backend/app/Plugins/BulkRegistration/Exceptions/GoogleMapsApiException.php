<?php

namespace App\Plugins\BulkRegistration\Exceptions;

use App\Exceptions\MpmException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Thrown when a Google Maps API request fails during bulk registration
 * geocoding.
 */
class GoogleMapsApiException extends MpmException {
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
