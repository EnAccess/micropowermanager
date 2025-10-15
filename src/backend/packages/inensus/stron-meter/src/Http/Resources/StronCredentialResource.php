<?php

namespace Inensus\StronMeter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Inensus\StronMeter\Models\StronCredential;

class StronCredentialResource extends JsonResource {
    public function __construct(StronCredential $stronCredential) {
        parent::__construct($stronCredential);
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return Request
     */
    public function toArray($request) {
        $credentials = $this->resource->toArray();
        $credentials['alert'] = $this->alertType($this->resource->is_authenticated);

        return $credentials;
    }

    /**
     * @return array{type: string, message:string}
     */
    private function alertType(?bool $authenticationStatus): array {
        return match ($authenticationStatus) {
            true => [
                'type' => 'success',
                'message' => 'Authentication Successful',
            ],
            false => [
                'type' => 'error',
                'message' => 'Authentication failed, please check your credentials',
            ],
            default => [
                'type' => 'warning',
                'message' => 'An error occurred while reaching out to Spark Meter. Please try it again later.',
            ],
        };
    }
}
