<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionAdvancedController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionProviderController;


Route::prefix('transactions')->group(function () {
    Route::get('/', [TransactionController::class, 'index']);
    Route::get('/search', [TransactionController::class, 'search']);
    Route::get('/cancelled', [TransactionController::class, 'cancelled']);
    Route::get('/confirmed', [TransactionController::class, 'confirmed']);
    Route::get('/analytics/{period}', [TransactionAdvancedController::class, 'compare'])->where('period', '[0-3]+');
    Route::get('/advanced', [TransactionAdvancedController::class, 'searchAdvanced']);
    Route::get('/{id}', [TransactionController::class, 'show'])->where('id', '[0-9]+');
});

Route::get('transaction-providers/', [TransactionProviderController::class, 'index']);
