<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\SubTargetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class SubTarget.
 *
 * @property      int                  $id
 * @property      int                  $target_id
 * @property      int                  $connection_id
 * @property      int                  $revenue
 * @property      int                  $new_connections
 * @property      float                $connected_power
 * @property      float                $energy_per_month
 * @property      float                $average_revenue_per_month
 * @property      Carbon|null          $created_at
 * @property      Carbon|null          $updated_at
 * @property-read ConnectionGroup|null $connectionType
 * @property-read Target|null          $target
 */
class SubTarget extends BaseModel {
    /** @use HasFactory<SubTargetFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Target, $this>
     */
    public function target(): BelongsTo {
        return $this->belongsTo(Target::class);
    }

    /**
     * @return BelongsTo<ConnectionGroup, $this>
     */
    // FIXME: Yepp... `connectionType` actually relates to `ConnectionGroup`
    public function connectionType(): BelongsTo {
        return $this->belongsTo(ConnectionGroup::class, 'connection_id');
    }
}
