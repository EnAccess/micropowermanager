<?php

namespace App\Models\Transaction;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class TransactionConflicts
 *
 * @package  App
 * @property int id
 * @property string state
 */
class TransactionConflicts extends BaseModel
{
    protected $connection = 'test_company_db';
    public function transaction(): MorphTo
    {
        return $this->morphTo();
    }
}
