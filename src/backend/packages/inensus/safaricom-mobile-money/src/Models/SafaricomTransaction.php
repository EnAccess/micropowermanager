<?php

namespace Inensus\SafaricomMobileMoney\Models;

use App\Models\Base\BaseModel;
use Inensus\SafaricomMobileMoney\Enums\TransactionStatus;

class SafaricomTransaction extends BaseModel {
    protected $table = 'safaricom_transactions';

    protected $fillable = [
        'reference_id',
        'amount',
        'phone_number',
        'account_reference',
        'transaction_desc',
        'status',
        'mpesa_receipt_number',
        'transaction_date',
        'response_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'response_data' => 'array',
        'status' => TransactionStatus::class,
    ];

    public function isPending(): bool {
        return $this->status === TransactionStatus::PENDING;
    }

    public function isSuccessful(): bool {
        return $this->status === TransactionStatus::SUCCEEDED;
    }

    public function isFailed(): bool {
        return $this->status === TransactionStatus::FAILED;
    }
}
