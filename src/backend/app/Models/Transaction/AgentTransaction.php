<?php

namespace App\Models\Transaction;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int    $agent_id
 * @property int    $mobile_device_id
 * @property int    $status
 * @property string $sender
 */
class AgentTransaction extends BasePaymentProviderTransaction {
    public const RELATION_NAME = 'agent_transaction';

    /**
     * @return MorphOne<Transaction, $this>
     */
    public function transaction(): MorphOne {
        return $this->morphOne(Transaction::class, 'original_transaction');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function manufacturerTransaction(): MorphTo {
        return $this->morphTo();
    }

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
