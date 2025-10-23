<?php

namespace App\Models;

use App\Models\Address\Address;
use App\Models\Base\BaseModel;
use Database\Factories\ManufacturerFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property      int                      $id
 * @property      string                   $type
 * @property      string                   $name
 * @property      string|null              $website
 * @property      string|null              $contact_person
 * @property      string|null              $api_name
 * @property      Carbon|null              $created_at
 * @property      Carbon|null              $updated_at
 * @property-read Collection<int, Address> $address
 */
class Manufacturer extends BaseModel {
    /** @use HasFactory<ManufacturerFactory> */
    use HasFactory;

    public const RELATION_NAME = 'manufacturer';

    protected $hidden = ['api_name'];
    protected $guarded = [];

    /**
     * @return MorphMany<Address, $this>
     */
    public function address(): MorphMany {
        return $this->morphMany(Address::class, 'owner');
    }
}
