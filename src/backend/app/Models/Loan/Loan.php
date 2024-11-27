<?php

namespace App\Models\Loan;

use App\Models\Base\BaseModel;
use App\Models\PaymentHistory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends BaseModel {
    // related payment histories which are made for that loan
    public function paymentHistories(): HasMany {
        return $this->hasMany(PaymentHistory::class);
    }
}
