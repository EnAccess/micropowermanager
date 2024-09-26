<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'wavecom'], function () {
    Route::post('/upload', 'WaveComTransactionController@uploadTransaction');
});
