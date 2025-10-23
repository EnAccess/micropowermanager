<?php

namespace Inensus\MesombPaymentProvider\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Inensus\MesombPaymentProvider\Models\MesombTransaction as MesombTransactionData;

/**
 * @mixin MesombTransactionData
 */
class MesombTransactionProcessingResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     data: array{
     *         type: string,
     *         pk: mixed,
     *         attributes: array{
     *             status: int,
     *             type: string,
     *             amount: float|int|null,
     *             fees: float|int|null,
     *             b_party: string|null,
     *             message: string|null
     *         }
     *     }
     * }
     */
    public function toArray(Request $request): array {
        return [
            'data' => [
                'type' => 'mesomb_transaction',
                'pk' => $this->pk,
                'attributes' => [
                    'status' => $this->status,
                    'type' => $this->type,
                    'amount' => $this->amount,
                    'fees' => $this->fees,
                    'b_party' => $this->b_party,
                    'message' => $this->message,
                ],
            ],
        ];
    }
}
