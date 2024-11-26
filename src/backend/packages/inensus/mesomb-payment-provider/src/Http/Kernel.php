<?php

namespace Inensus\MesombPaymentProvider\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Inensus\MesombPaymentProvider\Http\Middleware\MesombTransactionRequest;

class Kernel extends HttpKernel {
    protected $routeMiddleware = [
        'mesomb.transaction.request' => MesombTransactionRequest::class,
    ];
}
