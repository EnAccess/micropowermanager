<?php

namespace App\Models\Transaction;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class TransactionConflicts.
 *
 * @property int    $id
 * @property string $state
 */
class TransactionConflicts extends BaseModel {
    /**
     * @return MorphTo<Model, $this>
     */
    public function transaction(): MorphTo {
        return $this->morphTo();
    }
}
