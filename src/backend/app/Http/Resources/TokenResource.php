<?php

namespace App\Http\Resources;

use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Token
 */
class TokenResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            /* The code to enter on the device. */
            'token' => $this->token,
            /* One of `energy`, `time`, `unlock` or `reset`. */
            'token_type' => $this->token_type,
            /* The unit of `token_amount`, e.g. `kWh` or `days`. */
            'token_unit' => $this->token_unit,
            /* The credit the manufacturer issued, which can differ from the credit requested. */
            'token_amount' => $this->token_amount,
            /* The transaction the token was recorded against. */
            'transaction_id' => $this->transaction_id,
            'created_at' => $this->created_at,
        ];
    }
}
