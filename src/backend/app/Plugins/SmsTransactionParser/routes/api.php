<?php

use App\Plugins\SmsTransactionParser\Http\Controllers\SmsParsingRuleController;
use App\Plugins\SmsTransactionParser\Http\Controllers\SmsTransactionController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'sms-transaction-parser'], function () {
    Route::post('/install', [SmsParsingRuleController::class, 'install']);

    Route::group(['prefix' => 'parsing-rules'], function () {
        Route::get('/', [SmsParsingRuleController::class, 'index']);
        Route::post('/', [SmsParsingRuleController::class, 'store']);
        Route::put('/{id}', [SmsParsingRuleController::class, 'update']);
        Route::delete('/{id}', [SmsParsingRuleController::class, 'destroy']);
        Route::get('/{id}/messages', [SmsTransactionController::class, 'byParsingRule']);
    });

    Route::group(['prefix' => 'transactions'], function () {
        Route::get('/', [SmsTransactionController::class, 'index']);
    });
});
