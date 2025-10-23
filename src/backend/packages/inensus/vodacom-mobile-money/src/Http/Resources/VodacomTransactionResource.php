<?php

namespace Inensus\VodacomMobileMoney\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VodacomTransactionResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'data' => array_merge(parent::toArray($request), ['success' => true]),
        ];
    }

    /**
     * Return an error response.
     */
    public static function error(string $message, int $statusCode = 400): VodacomTransactionResource {
        $errorData = [
            'message' => $message,
            'success' => false,
        ];

        return new self((object) $errorData);
    }
}
