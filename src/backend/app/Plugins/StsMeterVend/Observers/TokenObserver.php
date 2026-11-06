<?php

namespace App\Plugins\StsMeterVend\Observers;

use App\Models\Meter\Meter;
use App\Models\Token;
use App\Plugins\StsMeterVend\Models\StsTransaction;

class TokenObserver {
    public function __construct(
        private StsTransaction $stsTransaction,
    ) {}

    public function created(Token $token): void {
        $device = $token->device;
        if ($device?->device_type !== Meter::RELATION_NAME) {
            return;
        }

        $issue = $this->stsTransaction->newQuery()
            ->where('token', $token->token)
            ->whereNull('token_id')
            ->whereRelation('stsMeter', 'mpm_meter_id', $device->device_id)
            ->first();

        if (!$issue instanceof StsTransaction) {
            return;
        }

        $issue->token_id = $token->id;
        $issue->transaction_id ??= $token->transaction_id;
        $issue->save();
    }
}
