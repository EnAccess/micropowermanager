<?php

namespace App\Models;

use App\Models\Address\Address;
use App\Models\Base\BaseModel;
use Database\Factories\ManufacturerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property string $api_name Name of manufacturer that is registed as alias in the Laravel service container. Can be used with `resolve('api_name')`
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
