<?php

namespace App\Plugins\CalinMeterV2\Http\Resources;

use App\Plugins\CalinMeterV2\Models\CalinV2Credential;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CalinV2Credential
 */
class CalinV2CredentialResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'api_url' => $this->api_url,
            'company' => $this->company,
            'user_id' => $this->user_id,
            'password' => $this->password,
        ];
    }
}
