<?php

namespace App\Plugins\VodacomMzPaymentProvider\Http\Resources;

use App\Plugins\VodacomMzPaymentProvider\Models\VodacomMzTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VodacomMzTransaction
 */
class VodacomTransactionResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        // return [
        //     'data' => array_merge(parent::toArray($request), ['success' => true]),
        // ];

        return [
            'response_code' => 'asdf',
            'response_desc' => $this->title,
            'simulation_id' => $this->id,
            'payment_details' => [
                'beneficiary' => 'user_id',
                'chargedType' => 'user_id',
                'chargedAmount' => 'user_id',
            ],

            // 'author' => UserResource::make($this->whenLoaded('author')),
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
