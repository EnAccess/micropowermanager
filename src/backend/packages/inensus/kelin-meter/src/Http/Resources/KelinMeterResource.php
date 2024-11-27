<?php

namespace Inensus\KelinMeter\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KelinMeterResource extends JsonResource {
    public function toArray($request) {
        return [
            'data' => [
                'type' => 'setting',
                'id' => $this->id,
                'attributes' => [
                    'id' => $this->id,
                    'meterName' => $this->meter_name,
                    'meterAddress' => $this->meter_address,
                    'owner' => $this->kelinCustomer->mpmPerson->name.' '.$this->kelinCustomer->mpmPerson->surname,
                    'terminalId' => $this->rtuId,
                    'kelinCustomer' => $this->kelinCustomer,
                ],
            ],
        ];
    }
}
