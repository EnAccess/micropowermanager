<?php

namespace App\Models\Loan;

use App\Models\Base\BaseModel;
use App\Models\PaymentHistory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property      int                             $id
 * @property      Carbon|null                     $created_at
 * @property      Carbon|null                     $updated_at
 * @property-read Collection<int, PaymentHistory> $paymentHistories
 */
class Loan extends BaseModel {
    /**
     * Related payment histories which are made for that loan.
     *
     * @return HasMany<PaymentHistory, $this>
     */
    public function paymentHistories(): HasMany {
        return $this->hasMany(PaymentHistory::class);
    }
}
