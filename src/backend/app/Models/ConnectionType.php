<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class ConnectionType.
 *
 * @property string $name
 */
class ConnectionType extends BaseModel {
    /** @return HasMany<SubTarget, $this> */
    public function subTargets(): HasMany {
        return $this->hasMany(SubTarget::class);
    }

    /** @return HasMany<Meter, $this> */
    public function meters(): HasMany {
        return $this->hasMany(Meter::class);
    }

    /** @return HasMany<Meter, $this> */
    public function meterCount(string $till): HasMany {
        return $this->meters()
            ->selectRaw('connection_type_id, count(*) as aggregate')
            ->where('created_at', '<=', $till)
            ->groupBy('connection_type_id');
    }

    /** @return Collection<int, \stdClass> */
    public function numberOfConnections(): Collection {
        return DB::connection('tenant')->table('meters')
            ->select(DB::connection('tenant')->raw('connection_type_id, count(id) as total'))
            ->groupBy('connection_type_id')
            ->get();
    }

    /** @return HasMany<SubConnectionType, $this> */
    public function subConnections(): HasMany {
        return $this->hasMany(SubConnectionType::class);
    }
}
