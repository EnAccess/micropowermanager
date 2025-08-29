<?php

namespace Inensus\KelinMeter\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KelinMeterStatusResource extends JsonResource {
    public function toArray($request) {
        // FIXME: This seems broken.
        return [
            'data' => [
                'type' => 'meter-status',
                'attributes' => [
                    'positiveActiveValue' => $this->bdZy, // @phpstan-ignore property.notFound
                    'positiveReactiveValue' => $this->bdZw, // @phpstan-ignore property.notFound
                    'invertedActiveValue' => $this->bdFy, // @phpstan-ignore property.notFound
                    'invertedReactiveValue' => $this->bdFw, // @phpstan-ignore property.notFound
                    'positiveActiveDailyPower' => $this->dlZy, // @phpstan-ignore property.notFound
                    'positiveReactiveDailyPower' => $this->dlZw, // @phpstan-ignore property.notFound
                    'invertedActiveDailyPower' => $this->dlFy, // @phpstan-ignore property.notFound
                    'invertedReactiveDailyPower' => $this->dlFw, // @phpstan-ignore property.notFound
                    'energyRemain' => $this->energyRemain, // @phpstan-ignore property.notFound
                    'moneyRemain' => $this->moneyRemain, // @phpstan-ignore property.notFound
                    'meterStatus' => $this->meterStatus, // @phpstan-ignore property.notFound
                    'openCoverCount' => $this->openCoverCount, // @phpstan-ignore property.notFound
                    'openTerminalCount' => $this->openTerminalCount, // @phpstan-ignore property.notFound
                    'owner' => $this->owner, // @phpstan-ignore property.notFound
                    'meterAddress' => $this->meterAddress, // @phpstan-ignore property.notFound
                ],
            ],
        ];
    }
}
