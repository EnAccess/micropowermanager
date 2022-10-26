<?php

namespace App\Models;

use App\Models\Transaction\Transaction;
use App\Relations\BelongsToMorph;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AgentBalanceHistory extends BaseModel
{
    protected $guarded = [];

    public function agent(): void
    {
        $this->belongsTo(Agent::class);
    }


    public function trigger(): MorphTo
    {
        return $this->morphTo();
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
