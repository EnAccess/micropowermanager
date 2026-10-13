<?php

namespace App\Plugins\OpenPaygo\Models;

use App\Models\Base\BaseModel;
use App\Models\Device;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $transaction_id
 * @property int         $device_id
 * @property int|null    $active_device_id
 * @property string      $operation
 * @property string      $generator_operation
 * @property int|null    $generator_value
 * @property int|null    $credit_days
 * @property int         $counter
 * @property int         $starting_code
 * @property string      $secret_key_ciphertext
 * @property string      $state
 * @property string|null $token
 * @property int|null    $next_counter
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OpenPaygoIssuanceReservation extends BaseModel {
    public const STATE_RESERVED = 'reserved';
    public const STATE_REQUESTING = 'requesting';
    public const STATE_UNCERTAIN = 'uncertain';
    public const STATE_GENERATED = 'generated';
    public const STATE_COMPLETED = 'completed';

    protected $table = 'openpaygo_issuance_reservations';

    protected $hidden = ['secret_key_ciphertext'];

    protected $casts = [
        'generator_value' => 'integer',
        'credit_days' => 'integer',
        'counter' => 'integer',
        'starting_code' => 'integer',
        'next_counter' => 'integer',
    ];

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo {
        return $this->belongsTo(Device::class);
    }
}
