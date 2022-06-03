<?php

namespace App\Models\Address;

use App\Models\City;
use App\Models\BaseModel;
use App\Models\GeographicalInformation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class Address
 *
 * @package  App\Models\Address
 * @property string $email
 * @property string $phone
 * @property string $street
 * @property int city_id
 * @property int is_primary
 */
class Address extends BaseModel
{

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected $hidden = ['owner_id', 'owner_type'];
    public static $rules = [
    'city_id' => 'required|exists:cities,id',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    // client & company
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function geo(): BelongsTo
    {
        return $this->belongsTo(GeographicalInformation::class);
    }
}
