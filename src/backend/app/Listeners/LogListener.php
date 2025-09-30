<?php

namespace App\Listeners;

use Illuminate\Database\Eloquent\Model;
use App\Events\NewLogEvent;
use App\Models\Log;

class LogListener {
    public function __construct(private Log $log) {}

    /**
     * @param array{user_id: int, affected: Model, action: string} $logData
     */
    public function storeLog(array $logData): void {
        $this->log->user_id = $logData['user_id'];
        $this->log->affected()->associate($logData['affected']);
        $this->log->action = $logData['action'];

        $this->log->save();
    }

    public function handle(NewLogEvent $event): void {
        $this->storeLog($event->logData);
    }
}
