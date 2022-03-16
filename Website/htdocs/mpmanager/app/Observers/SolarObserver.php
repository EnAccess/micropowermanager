<?php

namespace App\Observers;

use App\Models\Solar;

class SolarObserver
{

    /**
     * @var Solar
     */
    private $solar;

    public function __construct(Solar $solar)
    {

        $this->solar = $solar;
    }

    public function created(Solar $solar): void
    {
        //dispatch an event to get  weather data for mini-grid
        event(
            'solar.received',
            [
            'solar' => $solar,
            'mini_grid_id' => $solar->mini_grid_id,
            ]
        );
    }
}
