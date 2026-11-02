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
 * @property      list<string>|null        $previous_names
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

    protected static function booted(): void {
        // Device exports and import files carry the manufacturer name, so a renamed
        // manufacturer keeps its old names to stay resolvable by ManufacturerService::getByName().
        static::updating(function (self $manufacturer): void {
            if (!$manufacturer->isDirty('name')) {
                return;
            }

            $previousNames = [...($manufacturer->previous_names ?? []), $manufacturer->getOriginal('name')];
            $manufacturer->previous_names = array_values(array_unique(array_diff($previousNames, [$manufacturer->name])));
        });
    }

    protected function casts(): array {
        return [
            'previous_names' => 'array',
        ];
    }

    /**
     * @return MorphMany<Address, $this>
     */
    public function address(): MorphMany {
        return $this->morphMany(Address::class, 'owner');
    }
}
