<?php

namespace App\Plugins\VodacomMzPaymentProvider\Enums;

enum TransactionStatus: string {
    case PENDING = 'pending';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
}
