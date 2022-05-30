<?php

namespace App\Services;

use App\Models\TicketSettings;

class TicketSettingsService extends BaseService implements ISettingsService
{
    public function __construct(private TicketSettings $ticketSettings)
    {
        parent::__construct([$ticketSettings]);
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