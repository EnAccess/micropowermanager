<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Generic Token Model for all types of tokens i.e meter token, shs token, etc.
 *
 * @property string $token
 * @property string $token_type
 * @property string $token_unit
 * @property float  $token_amount
 * @property int    $transaction_id
 * @property int    $device_id
 */
class Token extends BaseModel {
    public const RELATION_NAME = 'token';
    public const TYPE_TIME = 'time';
    public const TYPE_ENERGY = 'energy';

    public const UNIT_DAYS = 'days';
    public const UNIT_WEEKS = 'weeks';
    public const UNIT_MONTHS = 'months';
    public const UNIT_KWH = 'kWh';

    protected $fillable = [
        'token',
        'token_type',
        'token_unit',
        'token_amount',
        'transaction_id',
        'device_id',
    ];

    public function device(): BelongsTo {
        return $this->belongsTo(Device::class);
    }

    public function __toString(): string {
        if ($this->token_type === self::TYPE_TIME) {
            return sprintf('Token: %s for %.1f %s', $this->token, $this->token_amount, $this->token_unit);
        }

        if ($this->token_type === self::TYPE_ENERGY) {
            return sprintf('Token: %s for %.3f %s', $this->token, $this->token_amount, $this->token_unit);
        }

        return 'Token: '.$this->token;
    }

    public function transaction(): BelongsTo {
        return $this->belongsTo(Transaction::class);
    }

    public function paymentHistories(): MorphOne {
        return $this->morphOne(PaymentHistory::class, 'paid_for');
    }
}
