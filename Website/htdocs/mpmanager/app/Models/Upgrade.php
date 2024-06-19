<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Upgrades.
 *
 * @property int $id
 * @property int $restriction_id
 * @property int $cost
 * @property int $period_in_months
 */
class Upgrade extends BaseModel
{
    public function restriction(): BelongsTo
    {
        return $this->belongsTo(Restriction::class);
    }
}
