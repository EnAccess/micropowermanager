<?php

namespace Inensus\SparkMeter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Inensus\SparkMeter\Models\SmCredential;

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
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $credentials = $this->resource->getAttributes();
        $credentials['alert'] = $this->alertType($this->is_authenticated);

        return $credentials;
    }

    /**
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
