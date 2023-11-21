<?php

namespace App\Models;

use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Token extends BaseModel
{
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}