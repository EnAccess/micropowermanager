<?php

namespace App\Models;

use App\Events\AssetPersonCreated;
use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class AssetPerson.
 *
 * @property int $asset_type_id
 * @property int $person_id
 * @property int $total_cost
 * @property int $down_payment
 * @property int $rate_count
 */
class AssetPerson extends BaseModel {
    protected $dispatchesEvents = [
        'created' => AssetPersonCreated::class,
    ];

    public function person(): BelongsTo {
        return $this->belongsTo(Person::class);
    }

    public function logs(): MorphMany {
        return $this->morphMany(Log::class, 'affected');
    }

    public function asset(): BelongsTo {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function rates(): HasMany {
        return $this->hasMany(AssetRate::class);
    }

    public function creator(): MorphTo {
        return $this->morphTo();
    }

    public function device(): BelongsTo {
        return $this->belongsTo(Device::class, 'device_serial');
    }
}
