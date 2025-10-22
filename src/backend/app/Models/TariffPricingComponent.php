<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Class TariffPricingComponent.
 *
 * @property      int         $id
 * @property      string      $name
 * @property      float       $price
 * @property      string      $owner_type
 * @property      int         $owner_id
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Model       $owner
 */
class TariffPricingComponent extends BaseModel {
    protected $guarded = [];

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo {
        return $this->morphTo();
    }
}
