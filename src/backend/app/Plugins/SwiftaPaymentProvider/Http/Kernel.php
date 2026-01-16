<?php

namespace App\Plugins\SwiftaPaymentProvider\Http;

use App\Plugins\SwiftaPaymentProvider\Http\Middleware\SwiftaMiddleware;
use App\Plugins\SwiftaPaymentProvider\Http\Middleware\SwiftaTransactionCallbackMiddleware;
use App\Plugins\SwiftaPaymentProvider\Http\Middleware\SwiftaTransactionMiddleware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel {
    protected $routeMiddleware = [
        'swifta.transaction' => SwiftaTransactionCallbackMiddleware::class,
        'swifta.validation' => SwiftaMiddleware::class,
        'swifta.transaction.validation' => SwiftaTransactionMiddleware::class,
    ];
}
