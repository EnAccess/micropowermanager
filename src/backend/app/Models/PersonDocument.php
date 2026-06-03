<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                          $id
 * @property      int                          $person_id
 * @property      string                       $category
 * @property      string                       $type
 * @property      string                       $name
 * @property      string|null                  $original_name
 * @property      string|null                  $mime_type
 * @property      int|null                     $file_size
 * @property      string|null                  $location
 * @property      array<array-key, mixed>|null $additional_json
 * @property      Carbon|null                  $created_at
 * @property      Carbon|null                  $updated_at
 * @property-read Person|null                  $person
 */
class PersonDocument extends BaseModel {
    public const CATEGORY_IDENTITY_RECORD = 'identity_record';
    public const CATEGORY_CUSTOMER_UPLOAD = 'customer_upload';

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo {
        return $this->belongsTo(Person::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'additional_json' => 'array',
        ];
    }
}
