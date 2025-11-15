<?php

use App\Http\Controllers\TransactionAdvancedController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionProviderController;
use Illuminate\Support\Facades\Route;

Route::prefix('transactions')->group(function () {
    Route::get('/', [TransactionController::class, 'index'])->middleware('permission:transactions');
    Route::get('/search', [TransactionController::class, 'search'])->middleware('permission:transactions');
    Route::get('/cancelled', [TransactionController::class, 'cancelled'])->middleware('permission:transactions');
    Route::get('/confirmed', [TransactionController::class, 'confirmed'])->middleware('permission:transactions');
    Route::get('/{id}', [TransactionController::class, 'show'])->where('id', '[0-9]+')->middleware('permission:transactions');
    Route::get('/analytics/{period}', [TransactionAdvancedController::class, 'compare'])->where('period', '[0-3]+')->middleware('permission:transactions');
    Route::get('/advanced', [TransactionAdvancedController::class, 'searchAdvanced'])->middleware('permission:transactions');
});

Route::get('transaction-providers/', [TransactionProviderController::class, 'index']);
