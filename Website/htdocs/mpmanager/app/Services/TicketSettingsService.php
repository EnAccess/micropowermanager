<?php

namespace App\Services;

use App\Models\TicketSettings;

class TicketSettingsService  implements ISettingsService
{
    public function __construct(private TicketSettings $ticketSettings)
    {

    }

    public function update($ticketSettings, $ticketSettingsData)
    {
        $ticketSettings->update($ticketSettingsData);
        $ticketSettings->fresh();

        return $ticketSettings;
    }

    public function get()
    {
       return $this->ticketSettings->newQuery()->first();
    }
}
