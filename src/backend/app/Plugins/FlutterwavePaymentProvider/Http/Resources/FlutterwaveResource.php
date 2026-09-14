<?php

declare(strict_types=1);

namespace App\Plugins\FlutterwavePaymentProvider\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FlutterwaveResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array {
        return [
            'redirectionUrl' => $this['redirectionUrl'],
            'reference' => $this['reference'],
            'error' => $this['error'],
        ];
    }
}
