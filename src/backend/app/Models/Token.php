<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

/**
 * Generic Token Model for all types of tokens i.e meter token, shs token, etc.
 *
 * @property string $token
 * @property float  $load
 * @property int    $transaction_id
 * @property string $token_type
 * @property int    $device_id
 * @property int    $token_amount
 */
class Token extends BaseModel {
    public const RELATION_NAME = 'token';
    public const TYPE_TIME = 'time';
    public const TYPE_ENERGY = 'energy';

    protected $fillable = [
        'token',
        'load',
        'transaction_id',
        'token_type',
        'device_id',
        'token_amount',
    ];

    public function device(): BelongsTo {
        return $this->belongsTo(Device::class);
    }

    public function __toString(): string {
        if ($this->token_type === self::TYPE_TIME) {
            return 'Token: '.$this->token.' for '.$this->token_amount.' days';
        }

        if ($this->token_type === self::TYPE_ENERGY) {
            return 'Token: '.$this->token.' for '.$this->load.' kWh';
        }

        return 'Token: '.$this->token;
    }

    public function transaction(): BelongsTo {
        return $this->belongsTo(Transaction::class);
    }

    public function paymentHistories(): MorphOne {
        return $this->morphOne(PaymentHistory::class, 'paid_for');
    }

    public function soldEnergyPerPeriod($startDate, $endDate): Builder {
        return $this::query()
            ->select(DB::raw(' SUM(load) as sold,YEARWEEK(created_at,3) as period'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('YEARWEEK(created_at,3)'));
    }
}
