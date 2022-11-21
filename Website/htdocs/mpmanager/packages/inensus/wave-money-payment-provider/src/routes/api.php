<?php
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'wave-money'], function () {
    Route::group(['prefix' => 'wave-money-credential'], function () {
        Route::get('/', 'WaveMoneyCredentialController@show');
        Route::put('/', 'WaveMoneyCredentialController@update');
    });
    Route::group(['prefix' => 'wave-money-transaction'], function () {
        Route::post('/start/{slug}', 'WaveMoneyController@startTransaction');
        Route::post('/callback/{slug}', 'WaveMoneyController@transactionCallBack');
    });
});
