<?php
use Illuminate\Support\Facades\Route;
Route::group(['prefix' => 'wave-money'], function () {
    Route::post('initialize-transaction', 'WaveMoneyController@initializeTransaction');
});
