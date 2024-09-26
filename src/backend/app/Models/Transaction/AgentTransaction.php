<?php

namespace App\Models\Transaction;

use App\Models\Agent;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use MPM\Transaction\FullySupportedTransactionInterface;

/**
 * @property int agent_id
 * @property int device_id
 * @property int status
 * @property string sender
 */
class AgentTransaction extends BaseModel implements IRawTransaction, FullySupportedTransactionInterface
{
    public const RELATION_NAME = 'agent_transaction';

    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'original_transaction');
    }

    public function manufacturerTransaction(): MorphTo
    {
        return $this->morphTo();
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function conflicts(): MorphMany
    {
        return $this->morphMany(TransactionConflicts::class, 'transaction');
    }

    public static function getTransactionName(): string
    {
        return self::RELATION_NAME;
    }
}
