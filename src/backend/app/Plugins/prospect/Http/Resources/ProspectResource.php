<?php

namespace Inensus\Prospect\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Inensus\Prospect\Models\ProspectCredential;

/**
 * @mixin ProspectCredential
 */
class ProspectResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array {
        if ($this->resource === null) {
            return [];
        }

        return [
            'data' => [
                'type' => 'credential',
                'id' => $this->id,
                'attributes' => [
                    'id' => $this->id,
                    'apiUrl' => $this->api_url,
                    'apiToken' => $this->api_token,
                ],
            ],
        ];
    }
}
