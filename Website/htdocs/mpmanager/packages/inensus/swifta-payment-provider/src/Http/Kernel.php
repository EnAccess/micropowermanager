<?php
namespace App\Http;


use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Inensus\SwiftaPaymentProvider\Http\Middleware\SwiftaTransactionCallbackMiddleware;
use Inensus\SwiftaPaymentProvider\Http\Middleware\SwiftaTransactionMiddleware;
use Inensus\SwiftaPaymentProvider\Http\Middleware\SwiftaMiddleware;


class Kernel extends HttpKernel
{

    protected $routeMiddleware = [
        'swifta.transaction' => SwiftaTransactionCallbackMiddleware::class,
        'swifta.validation' => SwiftaMiddleware::class,
        'swifta.transaction.validation' => SwiftaTransactionMiddleware::class,
    ];
}