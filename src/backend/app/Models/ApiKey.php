<?php

namespace App\Models;

use App\Models\Base\BaseModelCentral;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int    $id
 * @property int    $company_id
 * @property string $token_hash
 * @property bool   $active
 * @property string $name
 * @property string $last_used_at
 */
class ApiKey extends BaseModelCentral {
    protected $table = 'api_keys';

    protected $fillable = [
        'company_id',
        'name',
        'token_hash',
        'active',
        'last_used_at',
    ];

    /**
     * Scope query to only include active API keys.
     *
     * @param Builder<ApiKey> $query
     *
     * @return Builder<ApiKey>
     */
    protected function scopeActive(Builder $query): Builder {
        return $query->where('active', true);
    }
}
