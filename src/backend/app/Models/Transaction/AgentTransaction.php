<?php

namespace App\Models\Transaction;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int    $agent_id
 * @property int    $mobile_device_id
 * @property int    $status
 * @property string $sender
 */
class AgentTransaction extends BasePaymentProviderTransaction {
    public const RELATION_NAME = 'agent_transaction';

    /**
     * @return BelongsTo<Agent, $this>
     */
    public function agent(): BelongsTo {
        return $this->belongsTo(Agent::class);
    }

    /**
     * @return MorphMany<TransactionConflicts, $this>
     */
    public function conflicts(): MorphMany {
        return $this->morphMany(TransactionConflicts::class, 'transaction');
    }

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
