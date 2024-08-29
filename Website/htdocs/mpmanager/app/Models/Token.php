<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Token extends BaseModel
{
    public const RELATION_NAME = 'token';

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function paymentHistories(): MorphOne
    {
        return $this->morphOne(PaymentHistory::class, 'paid_for');
    }
}
