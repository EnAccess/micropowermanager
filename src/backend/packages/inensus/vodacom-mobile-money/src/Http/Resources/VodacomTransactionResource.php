<?php

namespace Inensus\VodacomMobileMoney\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VodacomTransactionResource extends JsonResource {
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
     * @return VodacomTransactionResource
     */
    public static function error(string $message, int $statusCode = 400): VodacomTransactionResource {
        $errorData = [
            'message' => $message,
            'success' => false,
        ];

        return new self((object) $errorData);
    }
}
