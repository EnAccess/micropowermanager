<?php

namespace App\Plugins\SparkMeter\Http\Resources;

use App\Plugins\SparkMeter\Models\SmCredential;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SmCredential
 */
class SparkMeterCredentialResource extends JsonResource {
    public function __construct(
        SmCredential $smCredential,
    ) {
        parent::__construct($smCredential);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'id' => $this->resource->id,
            'api_url' => $this->resource->api_url,
            'api_key' => $this->resource->api_key,
            'api_secret' => $this->resource->api_secret,
            'is_authenticated' => $this->resource->is_authenticated,
        ];
    }
}
