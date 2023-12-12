<?php

namespace App\Models\Address;

use App\Models\City;
use App\Models\BaseModel;
use App\Models\GeographicalInformation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class Address
 *
 * @package  App\Models\Address
 * @property null|string $email
 * @property null|string $phone
 * @property null|string $street
 * @property int $city_id
 * @property int $is_primary
 * @property int $owner_id
 * @property string $owner_type
 */
class Address extends BaseModel
{
    public const RELATION_NAME = 'address';
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

    public function geo(): MorphOne
    {
        return $this->morphOne(GeographicalInformation::class, 'owner');
    }

    public function setOwner(int $ownerId, string $ownerType): void
    {
        $this->owner_id = $ownerId;
        $this->owner_type = $ownerType;
    }

    public function setCityId(int $cityId): void
    {
        $this->city_id = $cityId;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function setIsPrimary(bool $isPrimary): void
    {
        $this->is_primary = $isPrimary;
    }

    public function setStreet(?string $street): void
    {
        $this->street = $street;
    }
}
