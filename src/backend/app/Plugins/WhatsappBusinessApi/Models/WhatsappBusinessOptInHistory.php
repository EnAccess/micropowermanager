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
 * @property      string|null $actor_type
 * @property      int|null    $actor_id
 * @property      string      $source
 * @property      string|null $note
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Person      $person
 */
class WhatsappBusinessOptInHistory extends BaseModel {
    public const SOURCE_ADMIN_UI = 'admin_ui';
    public const SOURCE_BULK_ACTION = 'bulk_action';
    public const SOURCE_CUSTOMER_REGISTRATION = 'customer_registration';
    public const SOURCE_WEBHOOK_COMMAND = 'webhook_command';
    public const SOURCE_SYSTEM = 'system';

    protected $table = 'whatsapp_business_opt_in_histories';
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
