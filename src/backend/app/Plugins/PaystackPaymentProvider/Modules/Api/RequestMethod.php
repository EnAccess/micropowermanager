<?php

declare(strict_types=1);

namespace App\Plugins\PaystackPaymentProvider\Modules\Api;

enum RequestMethod: string {
    case GET = 'GET';
    case POST = 'POST';
}
