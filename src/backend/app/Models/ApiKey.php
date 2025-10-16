<?php

namespace App\Models;

use App\Models\Base\BaseModelCentral;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int     $id
 * @property int     $company_id
 * @property string  $token_hash
 * @property bool    $active
 * @property string  $name
 * @property string  $last_used_at
 * @property Company $company
 */
class ApiKey extends BaseModelCentral {
    protected $table = 'api_keys';

    protected $fillable = [
        'name',
        'token_hash',
        'active',
        'last_used_at',
    ];

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo {
        return $this->belongsTo(Company::class);
    }

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
