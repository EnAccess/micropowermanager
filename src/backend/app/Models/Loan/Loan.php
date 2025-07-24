<?php

namespace App\Models\Loan;

use App\Models\Base\BaseModel;
use App\Models\PaymentHistory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends BaseModel {
    /**
     * Related payment histories which are made for that loan
     * @return HasMany<PaymentHistory, $this>
     */
    public function paymentHistories(): HasMany {
        return $this->hasMany(PaymentHistory::class);
    }
}
