<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'TicketController@index');
Route::get('/{trelloId}', 'TicketController@show');
Route::post('/', 'TicketController@create');

Route::group(['prefix' => 'api'], function () {
    Route::group(['prefix' => 'agents'], function () {

        Route::get('/{agent}', 'TicketController@indexAgentTickets');

    });
    Route::group(['prefix' => 'users'], function () {

        //all registered users
        Route::get('/', 'UserController@index');
        //new user
        Route::post('/', 'UserController@store');


    });
    Route::post('/export/outsource', 'ExportController@outsource');
    Route::get('/export', 'ExportController@index');
    Route::get('/export/download/{id}/book-keeping', 'ExportController@download');

    //trello controls if the callback route exists.
    Route::get('/watcher', function () {
        return 'okay';
    });
    Route::post('/watcher', 'WatcherController@store');


    Route::group(['prefix' => 'labels'], function () {
        Route::get('/', 'LabelController@index');
        Route::post('/', 'LabelController@store');

    });

    Route::get('/users', 'UserController@index');
    Route::post('/ticket', 'TicketController@create');
    Route::delete('/ticket', 'TicketController@destroy');
    Route::get('/tickets/user/{id}', 'TicketController@getTickets');
    Route::post('/tickets/users', 'UserController@store');
    Route::post('tickets/comments', 'CommentController@store');
});

