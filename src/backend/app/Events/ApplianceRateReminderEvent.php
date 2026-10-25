<?php

namespace App\Events;

use App\Models\ApplianceRate;

/**
 * Fired by the appliance-rate reminder command once per rate that is due.
 *
 * It lets notification channels (SMS today, WhatsApp or email next) react to
 * the same threshold decision without the command depending on any plugin.
 */
class ApplianceRateReminderEvent {
    public function __construct(
        public ApplianceRate $applianceRate,
        public bool $overdue = false,
    ) {}
}
