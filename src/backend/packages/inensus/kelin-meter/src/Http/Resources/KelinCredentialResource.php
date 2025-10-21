<?php

namespace Inensus\KelinMeter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Inensus\KelinMeter\Models\KelinCredential as KelinCredentialData;

/**
 * @mixin KelinCredentialData
 */
class KelinCredentialResource extends JsonResource {
    /**
     * @return array{
     *     data: array{
     *         type: 'credentials',
     *         id: int,
     *         attributes: array{
     *             username: string,
     *             password: string,
     *             isAuthenticated: bool,
     *             alert: array{type: string, message: string}
     *         }
     *     }
     * }
     */
    public function toArray(Request $request) {
        return [
            'data' => [
                'type' => 'credentials',
                'id' => $this->id,
                'attributes' => [
                    'username' => $this->username,
                    'password' => $this->password,
                    'isAuthenticated' => $this->is_authenticated,
                    'alert' => $this->alertType($this->is_authenticated),
                ],
            ],
        ];
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
                'message' => 'An error occurred while reaching out to Kelin Meter. Please try it again later.',
            ],
        };
    }
}
