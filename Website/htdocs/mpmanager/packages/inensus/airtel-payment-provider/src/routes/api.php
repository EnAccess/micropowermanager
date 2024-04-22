<?php

use Illuminate\Support\Facades\Route;
use Inensus\AirtelPaymentProvider\Http\Middleware\AirtelTransactionAuthorizationMiddleware;
use Inensus\AirtelPaymentProvider\Http\Middleware\AirtelTransactionValidationMiddleware;

Route::group(['prefix' => 'airtel'], function () {
    Route::get('/authentication', 'AirtelAuthenticationController@show')->middleware('jwt.verify');
    Route::group(['prefix' => '/transactions', 'middleware' => [AirtelTransactionAuthorizationMiddleware::class]],
        function () {
            Route::post('/validation',
                [
                    'uses' => 'AirtelTransactionController@validation',
                    'middleware' => [AirtelTransactionValidationMiddleware::class,]
                ]);
            Route::post('/enquiry', [
                'uses' => 'AirtelTransactionController@enquiry',

            ]);
            Route::post('/process', [
                'uses' => 'AirtelTransactionController@process',

            ]);
            Route::post('/test', [
                'uses' => 'AirtelTransactionController@test'
            ]);
        });
});