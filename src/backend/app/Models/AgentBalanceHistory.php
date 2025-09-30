<?php

namespace App\Models;

use Database\Factories\AgentBalanceHistoryFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Base\BaseModel;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $date
 * @property string $day
 * @property string $period
 * @property float  $revenue
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
