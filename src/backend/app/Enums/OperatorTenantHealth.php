<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

// The docblocks below are public and get rendered into the API docs.

/**
 * How recently a tenant last transacted, as shown on the operator dashboard.
 */
enum OperatorTenantHealth: string {
    /** Transacted within the active window. */
    case Active = 'active';
    /** Quiet for longer than the active window — a possible churn signal. */
    case Watch = 'watch';
    /** Quiet for longer than the watch window, or never transacted at all. */
    case Dormant = 'dormant';

    public static function fromLastActiveAt(?CarbonInterface $lastActiveAt): self {
        if (!$lastActiveAt instanceof CarbonInterface) {
            return self::Dormant;
        }

        // Carbon 3 returns a signed float here; truncating gives whole elapsed
        // days, so "7 days and 22 hours ago" still counts as day 7.
        $days = (int) $lastActiveAt->diffInDays(Carbon::now());

        if ($days <= (int) config('micropowermanager.operator_dashboard.health.active_days')) {
            return self::Active;
        }

        if ($days <= (int) config('micropowermanager.operator_dashboard.health.watch_days')) {
            return self::Watch;
        }

        return self::Dormant;
    }
}
