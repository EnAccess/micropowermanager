<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'vodacom'], function () {
    Route::group(['prefix' => 'payment'], function () {
        Route::post('/simulation', 'VodacomTransactionController@validateTransaction');
        Route::post('/execution', 'VodacomTransactionController@processTransaction');
        Route::post('/query', 'VodacomTransactionController@transactionEnquiryStatus');
    });
});
