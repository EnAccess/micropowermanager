<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Modules\Api;

enum RequestMethod: string {
    case GET = 'GET';
    case POST = 'POST';
}
