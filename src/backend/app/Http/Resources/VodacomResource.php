<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VodacomResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request): array {
        return [
            'data' => array_merge(parent::toArray($request), ['success' => true]),
        ];
    }

    /**
     * Return an error response.
     *
     * @param string $message
     * @param int    $statusCode
     *
     * @return JsonResponse
     */
    public static function error(string $message, int $statusCode = 400): JsonResponse {
        return response()->json([
            'data' => [
                'message' => $message,
                'success' => false,
            ],
        ], $statusCode);
    }
}
