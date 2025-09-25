<?php

namespace Inensus\SafaricomMobileMoney\Enums;

enum TransactionStatus: string {
    case INITIATED = 'initiated';
    case PENDING = 'pending';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case TIMEOUT = 'timeout';
    case VALIDATED = 'validated';
    case CONFIRMED = 'confirmed';
}
