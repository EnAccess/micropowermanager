<?php

namespace App\Listeners;

use App\Models\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log as ConsoleLog;

// FIXME: Shouldn't this be queue-able?
// class LogListener implements ShouldQueue {
class LogListener {
    /**
     * @var Log
     */
    private $log;

    public function __construct(Log $log) {
        $this->log = $log;
    }

    public function storeLog($logData): void {
        $this->log->user_id = $logData['user_id'];
        $this->log->affected()->associate($logData['affected']);
        $this->log->action = $logData['action'];

        $this->log->save();
    }

    public function handle(Dispatcher $eventData): void {
        // For debugging
        ConsoleLog::info('Log data received:', ['data' => $eventData]);

        $this->storeLog($eventData);
    }
}
