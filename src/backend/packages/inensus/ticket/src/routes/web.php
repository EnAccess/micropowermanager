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
        Route::get('/', [TicketController::class, 'index'])->middleware('permission:tickets.view');
        Route::post('/', [TicketCustomerController::class, 'store'])->middleware('permission:tickets.create');
        Route::delete('/{ticketId}', [TicketController::class, 'destroy'])->middleware('permission:tickets.delete');
        Route::get('/{id}', [TicketController::class, 'show'])->middleware('permission:tickets.view');
    });

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [TicketUserController::class, 'index']);
        Route::post('/external', [TicketUserController::class, 'storeExternal']);
    });
    Route::group(['prefix' => 'agents'], function () {
        Route::get('/{agentId}', [TicketAgentController::class, 'index']);
    });
    Route::group(['prefix' => 'labels'], function () {
        Route::get('/', [TicketCategoryController::class, 'index'])->middleware('permission:tickets.view');
        Route::post('/', [TicketCategoryController::class, 'store'])->middleware('permission:tickets.create');
    });
    Route::get('/tickets/user/{id}', [TicketCustomerController::class, 'index']);
    Route::post('tickets/comments', [TicketCommentController::class, 'store'])->middleware('permission:tickets.update');

    Route::get('/reports', [TicketOutsourcePayoutReportController::class, 'index'])->middleware('permission:tickets.export');
    Route::post('/reports/generate_report', [TicketOutsourcePayoutReportController::class, 'outsource'])->middleware('permission:tickets.export');
    Route::get('/reports/download/{id}', [TicketOutsourcePayoutReportController::class, 'download'])->middleware('permission:tickets.export');
});
