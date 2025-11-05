<?php

use Illuminate\Support\Facades\Route;
use Inensus\Ticket\Http\Controllers\TicketAgentController;
use Inensus\Ticket\Http\Controllers\TicketCategoryController;
use Inensus\Ticket\Http\Controllers\TicketCommentController;
use Inensus\Ticket\Http\Controllers\TicketController;
use Inensus\Ticket\Http\Controllers\TicketCustomerController;
use Inensus\Ticket\Http\Controllers\TicketOutsourcePayoutReportController;
use Inensus\Ticket\Http\Controllers\TicketUserController;

Route::group(['prefix' => 'api'], function () {
    Route::group(['prefix' => 'ticket'], function () {
        Route::get('/', [TicketController::class, 'index']);
        Route::post('/', [TicketCustomerController::class, 'store']);
        Route::delete('/{ticketId}', [TicketController::class, 'destroy']);
        Route::get('/{id}', [TicketController::class, 'show']);
    });

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [TicketUserController::class, 'index']);
        Route::post('/external', [TicketUserController::class, 'storeExternal']);
    });
    Route::group(['prefix' => 'agents'], function () {
        Route::get('/{agentId}', [TicketAgentController::class, 'index']);
    });
    Route::group(['prefix' => 'labels'], function () {
        Route::get('/', [TicketCategoryController::class, 'index']);
        Route::post('/', [TicketCategoryController::class, 'store']);
    });
    Route::get('/tickets/user/{id}', [TicketCustomerController::class, 'index']);
    Route::post('tickets/comments', [TicketCommentController::class, 'store']);

    Route::get('/reports', [TicketOutsourcePayoutReportController::class, 'index']);
    Route::post('/reports/generate_report', [TicketOutsourcePayoutReportController::class, 'outsource']);
    Route::get('/reports/download/{id}', [TicketOutsourcePayoutReportController::class, 'download']);
});
