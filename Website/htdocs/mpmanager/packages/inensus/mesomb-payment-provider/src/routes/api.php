<?php

use Illuminate\Support\Facades\Route;
use Inensus\MesombPaymentProvider\Http\Middleware\MesombTransactionRequest;

Route::group(['prefix' => 'mesomb', 'middleware' => [MesombTransactionRequest::class]], function () {
    Route::post('/', 'MesombPaymentProviderController@store');
});
