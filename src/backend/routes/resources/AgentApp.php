<?php

use App\Http\Controllers\AgentAuthController;
use App\Http\Controllers\AgentFirebaseController;
use App\Http\Controllers\AgentBalanceController;
use App\Http\Controllers\AgentCustomerController;
use App\Http\Controllers\AgentCustomersPaymentHistoryController;
use App\Http\Controllers\AgentTransactionsController;
use App\Http\Controllers\AgentSoldApplianceController;
use App\Http\Controllers\AgentAssignedAppliancesController;
use App\Http\Controllers\AgentTicketController;
use App\Http\Controllers\AgentCustomerTicketController;
use App\Http\Controllers\AgentDashboardBoxesController;
use App\Http\Controllers\AgentDashboardBalanceHistoryController;
use App\Http\Controllers\AgentDashboardRevenueController;
use Illuminate\Support\Facades\Route;

// Android App Services
Route::prefix('app')->group(function () {
    // Auth routes
    Route::post('login', [AgentAuthController::class, 'login']);
    Route::post('logout', [AgentAuthController::class, 'logout']);
    Route::post('refresh', [AgentAuthController::class, 'refresh']);
    Route::get('me', [AgentAuthController::class, 'me']);

    // Protected agent routes
    Route::middleware(['jwt.verify:agent', 'agent_api'])->prefix('agents')->group(function () {
        Route::post('/firebase', [AgentFirebaseController::class, 'update']);
        Route::get('/balance', [AgentBalanceController::class, 'show']);

        // Customer routes
        Route::prefix('customers')->group(function () {
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

        // Transaction routes
        Route::prefix('transactions')->group(function () {
            Route::get('/', [AgentTransactionsController::class, 'index']);
            Route::get('/{customerId}', [AgentTransactionsController::class, 'show']);
        });

        // Appliance routes
        Route::prefix('appliances')->group(function () {
            Route::get('/', [AgentSoldApplianceController::class, 'index']);
            Route::get('/{customerId}', [AgentSoldApplianceController::class, 'show']);
            Route::post('/', [AgentSoldApplianceController::class, 'store'])
                ->middleware('agent.balance')
                ->name('agent-sell-appliance');
        });

        // Appliance types routes
        Route::prefix('appliance_types')->group(function () {
            Route::get('/', [AgentAssignedAppliancesController::class, 'index']);
        });

        // Ticket routes
        Route::prefix('ticket')->group(function () {
            Route::get('/', [AgentTicketController::class, 'index']);
            Route::get('/{ticketId}', [AgentTicketController::class, 'show']);
            Route::get('/customer/{customerId}', [AgentCustomerTicketController::class, 'show']);
            Route::post('/', [AgentTicketController::class, 'store']);
        });

        // Dashboard routes
        Route::prefix('dashboard')->group(function () {
            Route::prefix('boxes')->group(function () {
                Route::get('/', [AgentDashboardBoxesController::class, 'show']);
            });

            Route::prefix('graph')->group(function () {
                Route::get('/', [AgentDashboardBalanceHistoryController::class, 'show']);
            });

            Route::prefix('revenue')->group(function () {
                Route::get('/', [AgentDashboardRevenueController::class, 'show']);
            });
        });
    });
});
