<?php

namespace App\Models;

use App\Models\Address\Address;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Manufacturer extends BaseModel {
    use HasFactory;

    public const RELATION_NAME = 'manufacturer';

    protected $hidden = ['api_name'];
    protected $guarded = [];

    public function address(): MorphMany {
        return $this->morphMany(Address::class, 'owner');
    }
}
