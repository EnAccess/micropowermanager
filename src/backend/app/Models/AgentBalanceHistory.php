<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Transaction\Transaction;
use Database\Factories\AgentBalanceHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property      int              $id
 * @property      int              $agent_id
 * @property      string           $trigger_type
 * @property      int              $trigger_id
 * @property      float            $amount
 * @property      float            $available_balance
 * @property      float            $due_to_supplier
 * @property      int|null         $transaction_id
 * @property      Carbon|null      $created_at
 * @property      Carbon|null      $updated_at
 * @property-read Agent|null       $agent
 * @property-read Transaction|null $transaction
 * @property-read Model            $trigger
 */
class AgentBalanceHistory extends BaseModel {
    /** @use HasFactory<AgentBalanceHistoryFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<Agent, $this>
     */
    public function agent(): BelongsTo {
        return $this->belongsTo(Agent::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function trigger(): MorphTo {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo {
        return $this->belongsTo(Transaction::class);
    }
}
