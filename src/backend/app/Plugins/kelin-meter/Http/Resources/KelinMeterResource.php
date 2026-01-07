<?php

namespace Inensus\KelinMeter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Inensus\KelinMeter\Models\KelinCustomer;
use Inensus\KelinMeter\Models\KelinMeter as KelinMeterData;

/**
 * @mixin KelinMeterData
 */
class KelinMeterResource extends JsonResource {
    /**
     * @return array{
     *     data: array{
     *         type: 'setting',
     *         id: int,
     *         attributes: array{
     *             id: int|string,
     *             meterName: string,
     *             meterAddress: string,
     *             owner: string,
     *             terminalId: int,
     *             kelinCustomer: KelinCustomer
     *         }
     *     }
     * }
     */
    public function toArray(Request $request) {
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
