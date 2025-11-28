<?php

use App\Http\Controllers\TicketAgentController;
use App\Http\Controllers\TicketCategoryController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketCustomerController;
use App\Http\Controllers\TicketOutsourcePayoutReportController;
use App\Http\Controllers\TicketUserController;
use Illuminate\Support\Facades\Route;

// Tickets
Route::group(['prefix' => 'tickets', 'middleware' => 'jwt.verify'], static function () {
    Route::group(['prefix' => 'ticket'], static function () {
        Route::get('/', [TicketController::class, 'index'])->middleware('permission:tickets');
        Route::post('/', [TicketCustomerController::class, 'store'])->middleware('permission:tickets');
        Route::delete('/{ticketId}', [TicketController::class, 'destroy'])->middleware('permission:tickets');
        Route::get('/{id}', [TicketController::class, 'show'])->middleware('permission:tickets');
    });

    Route::group(['prefix' => 'users'], static function () {
        Route::get('/', [TicketUserController::class, 'index']);
        Route::post('/external', [TicketUserController::class, 'storeExternal']);
    });

    Route::group(['prefix' => 'agents'], static function () {
        Route::get('/{agentId}', [TicketAgentController::class, 'index']);
    });

    Route::group(['prefix' => 'labels'], static function () {
        Route::get('/', [TicketCategoryController::class, 'index'])->middleware('permission:tickets');
        Route::post('/', [TicketCategoryController::class, 'store'])->middleware('permission:tickets');
    });

    Route::get('/user/{id}', [TicketCustomerController::class, 'index']);
    Route::post('/comments', [TicketCommentController::class, 'store'])->middleware('permission:tickets');

    Route::get('/reports', [TicketOutsourcePayoutReportController::class, 'index'])->middleware('permission:reports');
    Route::get('/reports/download/{id}', [TicketOutsourcePayoutReportController::class, 'download'])->middleware('permission:reports');
});
