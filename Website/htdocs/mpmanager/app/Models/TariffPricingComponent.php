<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class TariffPricingComponent
 *
 * @package App\Models
 *
 * @property int $id
 * @property string $name
 * @property int $price
 * @property int $owner_id
 * @property string $owner_type
 */
class TariffPricingComponent extends BaseModel
{

    protected $guarded = [];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
