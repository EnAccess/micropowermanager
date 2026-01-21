<?php

namespace App\Plugins\KelinMeter\Http\Resources;

use App\Plugins\KelinMeter\Models\KelinCustomer;
use App\Plugins\KelinMeter\Models\KelinMeter as KelinMeterData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
