<?php

namespace Inensus\Ticket\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OutputJob implements ShouldQueue {
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    /**
     * @var integert
     */
    protected $val;

    /**
     * @var string
     */
    protected $queue_name;

    public function __construct(string $queue_name, int $val) {
        $this->queue_name = $queue_name;
        $this->val = $val;
    }

    public function handle(): void {
        Log::critical('redis', ['abc' => 'def', 'Val' => $this->val, 'queue' => $this->queue_name]);
    }
}
