<?php

// Web panel routes for agent
use App\Http\Controllers\AgentWebController;
use App\Http\Controllers\AgentAssignedApplianceWebController;
use App\Http\Controllers\AgentSoldApplianceWebController;
use App\Http\Controllers\AgentCommissionWebController;
use App\Http\Controllers\AgentReceiptWebController;
use App\Http\Controllers\AgentTransactionWebController;
use App\Http\Controllers\AgentBalanceHistoryWebController;
use App\Http\Controllers\AgentChargeWebController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'jwt.verify'])
    ->prefix('agents')
    ->group(function () {
        Route::get('/', [AgentWebController::class, 'index']);
        Route::get('/{agentId}', [AgentWebController::class, 'show'])->where('agentId', '[0-9]+');
        Route::post('/', [AgentWebController::class, 'store']);
        Route::put('/{agentId}', [AgentWebController::class, 'update']);
        Route::get('/search', [AgentWebController::class, 'search']);
        Route::post('/reset-password', [AgentWebController::class, 'resetPassword']);
        Route::delete('/{agentId}', [AgentWebController::class, 'destroy']);

        Route::prefix('assigned')->group(function () {
            Route::post('/', [AgentAssignedApplianceWebController::class, 'store']);
            Route::get('/{agentId}', [AgentAssignedApplianceWebController::class, 'index']);
        });

        Route::prefix('sold')->group(function () {
            Route::get('/{agentId}', [AgentSoldApplianceWebController::class, 'index']);
        });

        Route::prefix('commissions')->group(function () {
            Route::get('/', [AgentCommissionWebController::class, 'index']);
            Route::post('/', [AgentCommissionWebController::class, 'store']);
            Route::delete('/{agentCommissionId}', [AgentCommissionWebController::class, 'destroy']);
            Route::put('/{agentCommissionId}', [AgentCommissionWebController::class, 'update']);
        });

        Route::prefix('receipt')->group(function () {
            Route::get('/', [AgentReceiptWebController::class, 'index']);
            Route::get('/{agentId}', [AgentReceiptWebController::class, 'show']);
            Route::post('/', [AgentReceiptWebController::class, 'store']);
        });

        Route::prefix('transactions')->group(function () {
            Route::get('/{agentId}', [AgentTransactionWebController::class, 'index'])->where('agentId', '[0-9]+');
        });

        Route::prefix('balance')->group(function () {
            Route::prefix('history')->group(function () {
                Route::get('/{agentId}', [AgentBalanceHistoryWebController::class, 'index']);
            });
        });

        Route::prefix('charge')->group(function () {
            Route::post('/', [AgentChargeWebController::class, 'store']);
        });
    });
