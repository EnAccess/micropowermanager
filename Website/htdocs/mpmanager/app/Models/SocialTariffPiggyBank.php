<?php

namespace App\Models;

use App\Models\Meter\MeterParameter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class SocialTariffPiggyBank.
 *
 * @property int    $owner_id
 * @property string $owner_type
 * @property int    $savings
 */
class SocialTariffPiggyBank extends BaseModel
{
    protected $guarded = [];

    public function meter(): BelongsTo
    {
        return $this->belongsTo(MeterParameter::class);
    }

    public function socialTariff(): BelongsTo
    {
        return $this->belongsTo(SocialTariff::class);
    }
}
