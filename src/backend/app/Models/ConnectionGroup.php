<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class ConnectionGroup.
 */
class ConnectionGroup extends BaseModel {
    /** @return HasMany<Meter, $this> */
    public function meters(): HasMany {
        return $this->hasMany(Meter::class);
    }

    /** @return HasMany<Meter, $this> */
    public function metersCount(string $till): HasMany {
        return $this->meters()
            ->selectRaw('connection_group_id, count(*) as aggregate')
            ->where('created_at', '<=', $till)
            ->groupBy('connection_group_id');
    }
}
