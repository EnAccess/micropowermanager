<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'vodacom'], function () {
    Route::group(['prefix' => 'transactions'], function () {
        Route::post('/validation', 'VodacomTransactionController@validateTransaction');
        Route::post('/process', 'VodacomTransactionController@processTransaction');
        Route::post('/enquiry', 'VodacomTransactionController@transactionEnquiryStatus');
    });
});
