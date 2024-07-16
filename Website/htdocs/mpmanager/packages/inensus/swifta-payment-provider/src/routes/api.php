<?php

use Illuminate\Support\Facades\Route;
use Inensus\SwiftaPaymentProvider\Http\Middleware\SwiftaMiddleware;
use Inensus\SwiftaPaymentProvider\Http\Middleware\SwiftaTransactionCallbackMiddleware;
use Inensus\SwiftaPaymentProvider\Http\Middleware\SwiftaTransactionMiddleware;

Route::group(['prefix' => 'swifta', 'middleware' => [SwiftaMiddleware::class]], function () {
    Route::post('/validation', ['middleware' => SwiftaTransactionMiddleware::class, 'uses' => 'SwiftaPaymentProviderController@validation']);
    Route::post('/transaction', ['middleware' => SwiftaTransactionCallbackMiddleware::class, 'uses' => 'SwiftaPaymentProviderController@transaction']);
});
Route::group(['prefix' => 'swifta-payment'], function () {
    Route::get('/authentication', 'SwiftaAuthenticationController@show');
});
