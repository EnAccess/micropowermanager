<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;
use Database\Factories\ConnectionTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class ConnectionType.
 *
 * @property      int                                                              $id
 * @property      string                                                           $name
 * @property      Carbon|null                                                      $created_at
 * @property      Carbon|null                                                      $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Meter>             $meters
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SubConnectionType> $subConnections
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SubTarget>         $subTargets
 */
class ConnectionType extends BaseModel {
    /** @use HasFactory<ConnectionTypeFactory> */
    use HasFactory;

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
