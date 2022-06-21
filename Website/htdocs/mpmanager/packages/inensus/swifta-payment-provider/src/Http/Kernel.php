<?php
namespace App\Http;


use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Inensus\SwiftaPaymentProvider\Http\Middleware\SwiftaTransactionRequest;
use Inensus\SwiftaPaymentProvider\Http\Middleware\SwiftaValidationBeforeTransactionRequest;
use Inensus\SwiftaPaymentProvider\Http\Middleware\SwiftaValidationRequest;


class Kernel extends HttpKernel
{

    protected $routeMiddleware = [
        'swifta.transaction' => SwiftaTransactionRequest::class,
        'swifta.validation' => SwiftaValidationRequest::class,
        'swifta.transaction.validation' => SwiftaValidationBeforeTransactionRequest::class,
    ];
}