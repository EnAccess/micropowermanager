<?php

namespace App\Models\Transaction;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

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
    /**
     * @return MorphTo<Model, $this>
     */
    public function transaction(): MorphTo {
        return $this->morphTo();
    }
}
