<?php

namespace App\Models\Transaction;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Class TransactionConflicts.
 *
 * @property      int         $id
 * @property      string      $transaction_type
 * @property      int         $transaction_id
 * @property      string      $state
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Model       $transaction
 */
class TransactionConflicts extends BaseModel {
    /** Provider and manufacturer errors are stored verbatim; cap them so one runaway message cannot dominate the row. */
    private const int STATE_MAX_LENGTH = 1000;

    /**
     * @return MorphTo<Model, $this>
     */
    public function transaction(): MorphTo {
        return $this->morphTo();
    }

    /**
     * @return Attribute<never, string>
     */
    protected function state(): Attribute {
        return Attribute::set(fn (?string $value): string => Str::limit($value ?? '', self::STATE_MAX_LENGTH));
    }
}
