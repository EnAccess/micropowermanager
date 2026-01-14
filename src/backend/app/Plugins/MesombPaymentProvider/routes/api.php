<?php

use App\Plugins\MesombPaymentProvider\Http\Middleware\MesombTransactionRequest;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'mesomb', 'middleware' => [MesombTransactionRequest::class]], function () {
    Route::post('/', 'MesombPaymentProviderController@store');
});
