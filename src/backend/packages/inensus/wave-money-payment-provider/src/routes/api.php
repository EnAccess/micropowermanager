<?php

use Illuminate\Support\Facades\Route;
use Inensus\WaveMoneyPaymentProvider\Http\Middleware\WaveMoneyTransactionCallbackMiddleware;
use Inensus\WaveMoneyPaymentProvider\Http\Middleware\WaveMoneyTransactionMiddleware;

Route::group(['prefix' => 'wave-money'], function () {
    Route::group(['prefix' => 'wave-money-credential'], function () {
        Route::get('/', 'WaveMoneyCredentialController@show');
        Route::put('/', 'WaveMoneyCredentialController@update');
    });
    Route::group(['prefix' => 'wave-money-transaction'], function () {
        Route::post(
            '/start/{slug}',
            ['middleware' => WaveMoneyTransactionMiddleware::class, 'uses' => 'WaveMoneyController@startTransaction']
        );
        Route::post('/callback/{slug}', [
            'middleware' => WaveMoneyTransactionCallbackMiddleware::class,
            'uses' => 'WaveMoneyController@transactionCallBack',
        ]);
    });
});
