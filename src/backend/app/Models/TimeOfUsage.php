<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      int         $tariff_id
 * @property      string      $start
 * @property      string      $end
 * @property      float       $value
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Tariff|null $tariff
 */
class TimeOfUsage extends BaseModel {
    /**
     * @return BelongsTo<Tariff, $this>
     */
    public function tariff(): BelongsTo {
        return $this->belongsTo(Tariff::class);
    }
}
