<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Subscription.
 *
 * @property int    $id
 * @property int    $upgrade_id
 * @property string $expires
 * @property string $transaction_id
 */
class Subscription extends BaseModel
{
    public function upgrade(): BelongsTo
    {
        return $this->belongsTo(Upgrade::class);
    }
}
