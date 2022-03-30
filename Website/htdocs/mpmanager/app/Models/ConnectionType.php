<?php

namespace App\Models;

use App\Models\Meter\MeterParameter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class ConnectionType
 *
 * @package App\Models
 *
 * @property string $name
 */
class ConnectionType extends BaseModel
{
    protected $connection = 'test_company_db';
    public function subTargets(): HasMany
    {
        return $this->hasMany(SubTarget::class);
    }

    public function meterParameters(): HasMany
    {
        return $this->hasMany(MeterParameter::class);
    }

    public function meterParametersCount($till)
    {
        return $this->meterParameters()
            ->selectRaw('connection_type_id, count(*) as aggregate')
            ->where('created_at', '<=', $till)
            ->groupBy('connection_type_id');
    }

    public function numberOfConnections(): Collection
    {
        return DB::table('meter_parameters')
            ->select(DB::raw('connection_type_id, count(id) as total'))
            ->groupBy('connection_type_id')
            ->get();
    }

    public function subConnections(): HasMany
    {
        return $this->hasMany(SubConnectionType::class);
    }
}
