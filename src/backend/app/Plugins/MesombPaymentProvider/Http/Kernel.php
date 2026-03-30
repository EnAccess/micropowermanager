<?php

namespace App\Plugins\MesombPaymentProvider\Http;

use App\Plugins\MesombPaymentProvider\Http\Middleware\MesombTransactionRequest;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel {
    protected $routeMiddleware = [
        'mesomb.transaction.request' => MesombTransactionRequest::class,
    ];
}
