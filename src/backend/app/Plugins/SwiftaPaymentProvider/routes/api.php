<?php

use App\Plugins\SwiftaPaymentProvider\Http\Middleware\SwiftaMiddleware;
use App\Plugins\SwiftaPaymentProvider\Http\Middleware\SwiftaTransactionCallbackMiddleware;
use App\Plugins\SwiftaPaymentProvider\Http\Middleware\SwiftaTransactionMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'swifta', 'middleware' => [SwiftaMiddleware::class]], function () {
    Route::post('/validation', ['middleware' => SwiftaTransactionMiddleware::class, 'uses' => 'SwiftaPaymentProviderController@validation']);
    Route::post('/transaction', ['middleware' => SwiftaTransactionCallbackMiddleware::class, 'uses' => 'SwiftaPaymentProviderController@transaction']);
});
Route::group(['prefix' => 'swifta-payment'], function () {
    Route::get('/authentication', 'SwiftaAuthenticationController@show');
});
