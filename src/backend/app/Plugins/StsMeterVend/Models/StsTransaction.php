<?php

namespace App\Plugins\StsMeterVend\Models;

use App\Models\Token;
use App\Models\Transaction\BaseManufacturerTransaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int           $id
 * @property      int           $sts_meter_id
 * @property      int|null      $transaction_id
 * @property      int|null      $token_id
 * @property      string        $token_type
 * @property      Carbon        $issued_at
 * @property      int           $random_number
 * @property      float|null    $kwh
 * @property      string|null   $token
 * @property      Carbon|null   $created_at
 * @property      Carbon|null   $updated_at
 * @property-read StsMeter|null $stsMeter
 * @property-read Token|null    $issuedToken
 */
class StsTransaction extends BaseManufacturerTransaction {
    protected $table = 'sts_transactions';
    protected $guarded = [];
    protected $casts = [
        'issued_at' => 'datetime',
        'kwh' => 'float',
    ];

    /** @return BelongsTo<StsMeter, $this> */
    public function stsMeter(): BelongsTo {
        return $this->belongsTo(StsMeter::class);
    }

    /** @return BelongsTo<Token, $this> */
    public function issuedToken(): BelongsTo {
        return $this->belongsTo(Token::class, 'token_id');
    }
}
