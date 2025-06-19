<?php

namespace App\Listeners;

use App\Models\Log;

class LogListener {
    /**
     * @var Log
     */
    private $log;

    public function __construct(Log $log) {
        $this->log = $log;
    }

    public function storeLog(array $logData): void {
        $this->log->user_id = $logData['user_id'];
        $this->log->affected()->associate($logData['affected']);
        $this->log->action = $logData['action'];

        $this->log->save();
    }

    public function handle(array $logData): void {
        // Note: Laravel's dispatcher unpacks event's arguments when `handle` is called.
        // Hence, event's have to be fire like this
        // event('new.log', [
        //     'logData' => [
        //         'user_id' => 1,
        //         'affected' => $someDevice,
        //         'action' => 'Device infos updated from: ...',
        //     ],
        // ]);
        $this->storeLog($logData);
    }
}
