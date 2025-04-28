<?php

// Web panel routes for agent
use App\Http\Controllers\AgentAssignedApplianceWebController;
use App\Http\Controllers\AgentBalanceHistoryWebController;
use App\Http\Controllers\AgentChargeWebController;
use App\Http\Controllers\AgentCommissionWebController;
use App\Http\Controllers\AgentReceiptWebController;
use App\Http\Controllers\AgentSoldApplianceWebController;
use App\Http\Controllers\AgentTransactionWebController;
use App\Http\Controllers\AgentWebController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['api', 'jwt.verify'],
    'prefix' => 'agents',
], static function ($router) {
    Route::get('/', [AgentWebController::class, 'index']);
    Route::get('/{agentId}', [AgentWebController::class, 'show'])->where('agentId', '[0-9]+');
    Route::post('/', [AgentWebController::class, 'store']);
    Route::put('/{agentId}', [AgentWebController::class, 'update']);
    Route::get('/search', [AgentWebController::class, 'search']);
    Route::post('/reset-password', [AgentWebController::class, 'resetPassword']);
    Route::delete('/{agentId}', [AgentWebController::class, 'destroy']);

    Route::group(['prefix' => 'assigned'], function () {
        Route::post('/', [AgentAssignedApplianceWebController::class, 'store']);
        Route::get('/{agentId}', [AgentAssignedApplianceWebController::class, 'index']);
    });
    Route::group(['prefix' => 'sold'], function () {
        Route::get('/{agentId}', [AgentSoldApplianceWebController::class, 'index']);
    });
    Route::group(['prefix' => 'commissions'], function () {
        Route::get('/', [AgentCommissionWebController::class, 'index']);
        Route::post('/', [AgentCommissionWebController::class, 'store']);
        Route::delete('/{agentCommissionId}', [AgentCommissionWebController::class, 'destroy']);
        Route::put('/{agentCommissionId}', [AgentCommissionWebController::class, 'update']);
    });
    Route::group(['prefix' => 'receipt'], function () {
        Route::get('/', [AgentReceiptWebController::class, 'index']);
        Route::get('/{agentId}', [AgentReceiptWebController::class, 'show']);
        Route::post('/', [AgentReceiptWebController::class, 'store']);
    });
    Route::group(['prefix' => 'transactions'], function () {
        Route::get('/{agentId}', [AgentTransactionWebController::class, 'index'])->where('agentId', '[0-9]+');
    });
    Route::group(['prefix' => 'balance'], function () {
        Route::group(['prefix' => 'history'], function () {
            Route::get('/{agentId}', [AgentBalanceHistoryWebController::class, 'index']);
        });
    });
    Route::group(['prefix' => 'charge'], function () {
        Route::post('/', [AgentChargeWebController::class, 'store']);
    });
});
