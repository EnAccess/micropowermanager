<?php

namespace App\Listeners;

use App\Models\Log;
use Illuminate\Events\Dispatcher;

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

    public function subscribe(Dispatcher $event): void {
        $event->listen('new.log', [$this, 'storeLog']);
    }
}
