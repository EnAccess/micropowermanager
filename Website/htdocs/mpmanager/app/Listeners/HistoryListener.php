<?php

namespace App\Listeners;

use App\Events\HistoryEvent;
use App\Models\History;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class HistoryListener
{
    /**
     * @var History
     */
    public $historyModel;

    /**
     * Create the event listener.
     *
     * @param $historyModel
     */
    public function __construct(History $historyModel)
    {
        $this->historyModel = $historyModel;
    }

    /**
     * Saves a new event in to the histories table.
     *
     * @param mixed       $data    is an object which should be stored on the histories table
     * @param string      $content the stringified message about the entry
     * @param string      $type
     * @param string|null $field
     */
    public function save($data, string $content, string $type, ?string $field): void
    {
        $this->historyModel->target()->associate($data);
        $this->historyModel->content = $content;
        $this->historyModel->action = $type;
        $this->historyModel->field = $field;
        $this->historyModel->save();

        broadcast(new HistoryEvent($this->historyModel));
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen('history.create', 'App\Listeners\HistoryListener@save');
        $events->listen(
            'history.hearth.beat',
            function () {
                Log::debug('hearth beat of history listener');
            }
        );
    }
}
