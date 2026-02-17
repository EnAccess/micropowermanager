<?php

namespace App\Plugins\VodacomMobileMoney\Enums;

enum TransactionStatus: string {
    case PENDING = 'pending';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
}
