<?php

namespace App\Services;

use App\Models\TicketSettings;
use App\Services\Interfaces\ISettingsService;

/**
 * @implements ISettingsService<TicketSettings>
 */
class TicketSettingsService implements ISettingsService
{
    public function __construct(
        private TicketSettings $ticketSettings
    ) {
    }

    public function update($ticketSettings, array $ticketSettingsData): TicketSettings
    {
        $ticketSettings->update($ticketSettingsData);
        $ticketSettings->fresh();

        return $ticketSettings;
    }

    public function get(): TicketSettings
    {
        return $this->ticketSettings->newQuery()->first();
    }
}
