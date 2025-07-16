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
    /** @var array<string, string> */
    protected $dispatchesEvents = [
        'created' => AssetPersonCreated::class,
    ];

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo {
        return $this->belongsTo(Person::class);
    }

    /**
     * @return MorphMany<Log, $this>
     */
    public function logs(): MorphMany {
        return $this->morphMany(Log::class, 'affected');
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    /**
     * @return HasMany<AssetRate, $this>
     */
    public function rates(): HasMany {
        return $this->hasMany(AssetRate::class);
    }

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function creator(): MorphTo {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo {
        return $this->belongsTo(Device::class, 'device_serial');
    }
}
