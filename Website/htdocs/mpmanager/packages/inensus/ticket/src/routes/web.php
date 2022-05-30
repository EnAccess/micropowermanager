<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'TicketController@index');
Route::get('/{trelloId}', 'TicketController@show');

Route::group(['prefix' => 'api'], function () {
    Route::delete('/ticket/{ticketId}', 'TicketController@destroy');
    Route::post('/ticket', 'TicketCustomerController@store');
    Route::group(['prefix' => 'users'], function () {
        Route::get('/', 'TicketUserController@index');
        Route::post('/', 'TicketUserController@store');
    });
    Route::group(['prefix' => 'agents'], function () {
        Route::get('/{agentId}', 'TicketAgentController@index');
    });
    Route::group(['prefix' => 'labels'], function () {
        Route::get('/', 'TicketCategoryController@index');
        Route::post('/', 'TicketCategoryController@store');
    });
    Route::get('/tickets/user/{id}', 'TicketCustomerController@index');
    Route::post('tickets/comments', 'TicketCommentController@store');

    Route::get('/export', 'TicketExportController@index');
    Route::post('/export/outsource', 'TicketExportController@outsource');
    Route::get('/export/download/{id}/book-keeping', 'TicketExportController@download');
    //trello controls if the callback route exists.
    Route::get('/watcher', function () {
        return 'okay';
    });
    Route::post('/watcher', 'WatcherController@store');
});

