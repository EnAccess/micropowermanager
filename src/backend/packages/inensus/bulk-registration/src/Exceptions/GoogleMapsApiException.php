<?php

namespace Inensus\BulkRegistration\Exceptions;

class GoogleMapsApiException extends Exception {
    public function render($request) {
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
