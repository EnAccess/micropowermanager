<?php
use Illuminate\Support\Facades\Route;
use Inensus\SwiftaPaymentProvider\Http\Middleware\SwiftaTransactionRequest;
use Inensus\SwiftaPaymentProvider\Http\Middleware\SwiftaValidationRequest;
use Inensus\SwiftaPaymentProvider\Http\Middleware\SwiftaValidationBeforeTransactionRequest;

Route::group(['prefix' => 'swifta','middleware' => [SwiftaValidationRequest::class]], function () {
    Route::post('/validation', ['middleware' =>SwiftaValidationBeforeTransactionRequest::class , 'uses' => 'SwiftaPaymentProviderController@validation']);
    Route::post('/transaction', ['middleware' =>SwiftaTransactionRequest::class , 'uses' => 'SwiftaPaymentProviderController@transaction']);
});