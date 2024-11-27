<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentReceipt extends BaseModel {
    public const RELATION_NAME = 'agent_receipt';

    public function history(): BelongsTo {
        return $this->belongsTo(AgentBalanceHistory::class, 'last_controlled_balance_history_id', 'id');
    }

    public function agent(): BelongsTo {
        return $this->belongsTo(Agent::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function detail(): HasMany {
        return $this->hasMany(AgentReceiptDetail::class);
    }
}
