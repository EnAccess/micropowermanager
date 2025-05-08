<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

/**
 * Class SHSToken.
 *
 * @property string $token
 * @property string $token_type
 * @property float  $token_amount
 */
class SHSToken extends BaseModel {
    use HasFactory;

    protected $table = 'shs_tokens';
    public const RELATION_NAME = 'shs_token';
    public const TYPE_TIME = 'time';
    public const TYPE_ENERGY = 'energy';

    protected $fillable = [
        'token',
        'token_type',
        'token_amount',
        'transaction_id',
        'device_id',
    ];

    public function transaction(): BelongsTo {
        return $this->belongsTo(Transaction::class);
    }

    public function device(): BelongsTo {
        return $this->belongsTo(Device::class);
    }

    public function __toString(): string {
        if ($this->token_type === self::TYPE_TIME) {
            return 'Token: '.$this->token.' for '.$this->token_amount.' days';
        }

        return 'Token: '.$this->token.' for '.$this->token_amount.' kWh';
    }

    public function paymentHistories(): MorphOne {
        return $this->morphOne(PaymentHistory::class, 'paid_for');
    }

    public function soldTokensPerPeriod($startDate, $endDate): Builder {
        return $this::query()
            ->select(DB::raw('SUM(token_amount) as sold, YEARWEEK(created_at,3) as period'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('YEARWEEK(created_at,3)'));
    }
}
