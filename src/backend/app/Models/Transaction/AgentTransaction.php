<?php

namespace App\Models\Transaction;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property      int                                   $id
 * @property      int                                   $agent_id
 * @property      string                                $mobile_device_id
 * @property      int                                   $status
 * @property      Carbon|null                           $created_at
 * @property      Carbon|null                           $updated_at
 * @property      string|null                           $manufacturer_transaction_type
 * @property      int|null                              $manufacturer_transaction_id
 * @property-read Agent|null                            $agent
 * @property-read Collection<int, TransactionConflicts> $conflicts
 * @property-read Model|null                            $manufacturerTransaction
 * @property-read Transaction|null                      $transaction
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
