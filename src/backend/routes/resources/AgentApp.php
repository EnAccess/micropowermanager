<?php

use App\Http\Controllers\AgentAssignedAppliancesController;
use App\Http\Controllers\AgentAuthController;
use App\Http\Controllers\AgentBalanceController;
use App\Http\Controllers\AgentCustomerController;
use App\Http\Controllers\AgentCustomersPaymentHistoryController;
use App\Http\Controllers\AgentCustomerTicketController;
use App\Http\Controllers\AgentDashboardBalanceHistoryController;
use App\Http\Controllers\AgentDashboardBoxesController;
use App\Http\Controllers\AgentDashboardRevenueController;
use App\Http\Controllers\AgentFirebaseController;
use App\Http\Controllers\AgentSoldApplianceController;
use App\Http\Controllers\AgentTicketController;
use App\Http\Controllers\AgentTransactionsController;
use Illuminate\Support\Facades\Route;

// Android App Services
Route::group([
    'prefix' => 'app',
], function () {
    Route::post('login', [AgentAuthController::class, 'login']);
    Route::post('logout', [AgentAuthController::class, 'logout']);
    Route::post('refresh', [AgentAuthController::class, 'refresh']);
    Route::get('me', [AgentAuthController::class, 'me']);
    Route::group(['prefix' => 'agents', 'middleware' => ['jwt.verify:agent', 'agent_api']], function () {
        Route::post('/firebase', [AgentFirebaseController::class, 'update']);
        Route::get('/balance', [AgentBalanceController::class, 'show']);
        Route::group(['prefix' => 'customers'], function () {
            Route::get('/', [AgentCustomerController::class, 'index']);
            Route::get('/search', [AgentCustomerController::class, 'search']);
            Route::get(
                '/{customerId}/graph/{period}/{limit?}/{order?}',
                [AgentCustomersPaymentHistoryController::class, 'show']
            )->where('customerId', '[0-9]+');
            Route::get(
                '/graph/{period}/{limit?}/{order?}',
                [AgentCustomersPaymentHistoryController::class, 'index']
            );
        });
        Route::group(['prefix' => 'transactions'], function () {
            Route::get('/', [AgentTransactionsController::class, 'index']);
            Route::get('/{customerId}', [AgentTransactionsController::class, 'show']);
        });
        Route::group(['prefix' => 'appliances'], function () {
            Route::get('/', [AgentSoldApplianceController::class, 'index']);
            Route::get('/{customerId}', [AgentSoldApplianceController::class, 'show']);
            Route::post('/', [AgentSoldApplianceController::class, 'store'])
                ->middleware('agent.balance')
                ->name('agent-sell-appliance');
        });
        Route::group(['prefix' => 'appliance_types'], function () {
            Route::get('/', [AgentAssignedAppliancesController::class, 'index']);
        });
        Route::group(['prefix' => 'ticket'], function () {
            Route::get('/', [AgentTicketController::class, 'index']);
            Route::get('/{ticketId}', [AgentTicketController::class, 'show']);
            Route::get('/customer/{customerId}', [AgentCustomerTicketController::class, 'show']);
            Route::post('/', [AgentTicketController::class, 'store']);
        });
        Route::group(['prefix' => 'dashboard'], function () {
            Route::group(['prefix' => 'boxes'], function () {
                Route::get('/', [AgentDashboardBoxesController::class, 'show']);
            });
            Route::group(['prefix' => 'graph'], function () {
                Route::get('/', [AgentDashboardBalanceHistoryController::class, 'show']);
            });
            Route::group(['prefix' => 'revenue'], function () {
                Route::get('/', [AgentDashboardRevenueController::class, 'show']);
            });
        });
    });
});
