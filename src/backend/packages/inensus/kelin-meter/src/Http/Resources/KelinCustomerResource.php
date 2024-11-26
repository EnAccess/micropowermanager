<?php

namespace Inensus\KelinMeter\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KelinCustomerResource extends JsonResource {
    public function toArray($request) {
        return [
            'data' => [
                'type' => 'customer',
                'id' => $this->id,
                'attributes' => [
                    'id' => $this->id,
                    'customerNo' => $this->customer_no,
                    'mpmPerson' => $this->mpmPerson,
                    'phone' => $this->mobile,
                    'address' => $this->address,
                ],
            ],
        ];
    }
}
