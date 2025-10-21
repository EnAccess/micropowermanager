<?php

namespace Inensus\SteamaMeter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Inensus\SteamaMeter\Models\SteamaCredential;

class SteamaCredentialResource extends JsonResource {
    public function __construct(SteamaCredential $steamaCredential) {
        parent::__construct($steamaCredential);
    }

    /**
     * Transform the resource into an array.
     *
     * @return Request
     */
    public function toArray(Request $request) {
        $credentials = $this->resource->toArray();
        $credentials['alert'] = $this->alertType($this->resource->is_authenticated);

        return $credentials;
    }

    /**
     * @param bool $authenticationStatus
     *
     * @return array{
     *     type: string,
     *     message: string
     * }
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
