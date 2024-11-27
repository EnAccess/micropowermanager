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

    private function alertType($authenticationStatus) {
        switch ($authenticationStatus) {
            case true:
                return [
                    'type' => 'success',
                    'message' => 'Authentication Successful',
                ];
            case false:
                return [
                    'type' => 'error',
                    'message' => 'Authentication failed, please check your credentials',
                ];
            default:
                return [
                    'type' => 'warning',
                    'message' => 'An error occurred while reaching out to Spark Meter. Please try it again later.',
                ];
        }
    }
}
