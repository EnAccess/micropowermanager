<?php

namespace Inensus\KelinMeter\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KelinMeterStatusResource extends JsonResource {
    public function toArray($request) {
        return [
            'data' => [
                'type' => 'meter-status',
                'attributes' => [
                    'positiveActiveValue' => $this->bdZy,
                    'positiveReactiveValue' => $this->bdZw,
                    'invertedActiveValue' => $this->bdFy,
                    'invertedReactiveValue' => $this->bdFw,
                    'positiveActiveDailyPower' => $this->dlZy,
                    'positiveReactiveDailyPower' => $this->dlZw,
                    'invertedActiveDailyPower' => $this->dlFy,
                    'invertedReactiveDailyPower' => $this->dlFw,
                    'energyRemain' => $this->energyRemain,
                    'moneyRemain' => $this->moneyRemain,
                    'meterStatus' => $this->meterStatus,
                    'openCoverCount' => $this->openCoverCount,
                    'openTerminalCount' => $this->openTerminalCount,
                    'owner' => $this->owner,
                    'meterAddress' => $this->meterAddress,
                ],
            ],
        ];
    }
}
