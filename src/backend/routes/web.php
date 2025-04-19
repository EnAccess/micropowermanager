<?php

/**
 * |--------------------------------------------------------------------------
 * | Web Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register web routes for your application. These
 * | routes are loaded by the RouteServiceProvider within a group which
 * | contains the "web" middleware group. Now create something great!
 * |.
 */

use App\Http\Controllers\HomeController;
use App\Jobs\EnergyTransactionProcessor;
use App\Jobs\TokenProcessor;
use App\Misc\TransactionDataContainer;
use App\Models\Transaction\Transaction;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::prefix('jobs')
    ->middleware('auth')
    ->group(function () {
        Route::get('/token/{id}/{recreate?}', function (string $id, ?string $recreate = null) {
            $recreate = (bool) $recreate;
            TokenProcessor::dispatch(
                TransactionDataContainer::initialize(Transaction::find($id)),
                $recreate,
                1
            )->allOnConnection('redis')->onQueue(
                config('services.queues.token')
            );
        })->where('id', '[0-9]+')->name('jobs.token');

        Route::get('energy/{id}', function (string $id) {
            $transaction = Transaction::find($id);
            EnergyTransactionProcessor::dispatch($transaction)
                ->allOnConnection('redis')
                ->onQueue('energy_payment');
        });
    });

/*
 * the group in which events can be fired manually again.
 */
Route::prefix('events')
    ->middleware('auth')
    ->group(function () {
        /*
         * Confirms a transaction (again).
         */
        Route::get('transaction/confirm/{id}', function (string $id) {
            // find the transaction
            $transaction = Transaction::find($id);

            // fire event which confirms the transaction
            Event::dispatch('transaction.successfull', [$transaction]);
        })->where('id', '[0-9]+');
    });

Route::middleware('auth')->group(function () {
    // Route::get('/user-data', [AdminController::class, 'auth']);
});
