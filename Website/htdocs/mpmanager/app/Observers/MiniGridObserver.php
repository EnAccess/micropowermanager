<?php

namespace App\Observers;

use App\Models\GeographicalInformation;
use App\Models\MiniGrid;

class MiniGridObserver
{
    /**
     * @var GeographicalInformation
     */
    private $geographicalInformation;

    public function __construct(GeographicalInformation $geographicalInformation)
    {

        $this->geographicalInformation = $geographicalInformation;
    }

    /**
     * Handle the mini grid "created" event.
     *
     * @param MiniGrid $miniGrid
     *
     * @return void
     */
    public function created(MiniGrid $miniGrid)
    {
        $this->geographicalInformation->owner_type = 'mini-grid';
        $this->geographicalInformation->owner_id = $miniGrid->id;
        if (request()->input('geo_data')) {
            $this->geographicalInformation->points = implode(',', request()->input('geo_data'));
        } else {
            $this->geographicalInformation->points = "";
        }
        $this->geographicalInformation->save();
    }

    /**
     * Handle the mini grid "updated" event.
     *
     * @param MiniGrid $miniGrid
     *
     * @return void
     */
    public function updated(MiniGrid $miniGrid)
    {
        //
    }

    /**
     * Handle the mini grid "deleted" event.
     *
     * @param MiniGrid $miniGrid
     *
     * @return void
     */
    public function deleted(MiniGrid $miniGrid)
    {
        //
    }

    /**
     * Handle the mini grid "restored" event.
     *
     * @param MiniGrid $miniGrid
     *
     * @return void
     */
    public function restored(MiniGrid $miniGrid)
    {
        //
    }

    /**
     * Handle the mini grid "force deleted" event.
     *
     * @param MiniGrid $miniGrid
     *
     * @return void
     */
    public function forceDeleted(MiniGrid $miniGrid)
    {
        //
    }
}
