<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappBusinessApi\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      int         $person_id
 * @property      bool        $opted_in
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Person      $person
 */
class WhatsappBusinessOptIn extends BaseModel {
    protected $table = 'whatsapp_business_opt_ins';
    protected $guarded = [];

    protected $casts = [
        'opted_in' => 'boolean',
    ];

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
