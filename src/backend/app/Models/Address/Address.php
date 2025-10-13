<?php

namespace App\Models\Address;

use App\Models\Base\BaseModel;
use App\Models\City;
use App\Models\GeographicalInformation;
use Database\Factories\Address\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class Address.
 *
 * @property string|null             $email
 * @property string|null             $phone
 * @property string|null             $street
 * @property int                     $city_id
 * @property City                    $city
 * @property int                     $is_primary
 * @property int                     $owner_id
 * @property string                  $owner_type
 * @property Model                   $owner
 * @property GeographicalInformation $geo
 */
class Address extends BaseModel {
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    public const RELATION_NAME = 'address';
    protected $hidden = ['owner_id', 'owner_type'];

    /** @var array<string, string> */
    public static $rules = [
        'city_id' => 'required|exists:cities,id',
    ];

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo {
        return $this->belongsTo(City::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo {
        return $this->morphTo();
    }

    /**
     * @return MorphOne<GeographicalInformation, $this>
     */
    public function geo(): MorphOne {
        return $this->morphOne(GeographicalInformation::class, 'owner');
    }

    public function setOwner(int $ownerId, string $ownerType): void {
        $this->owner_id = $ownerId;
        $this->owner_type = $ownerType;
    }

    public function setCityId(int $cityId): void {
        $this->city_id = $cityId;
    }

    public function setPhone(?string $phone): void {
        $this->phone = $phone;
    }

    public function setEmail(?string $email): void {
        $this->email = $email;
    }

    public function setIsPrimary(bool $isPrimary): void {
        $this->is_primary = $isPrimary ? 1 : 0;
    }

    public function setStreet(?string $street): void {
        $this->street = $street;
    }
}
