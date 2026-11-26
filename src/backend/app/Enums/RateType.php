<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;

// The docblocks below are public and get rendered into the API docs.

/**
 * How often the installments of a sold appliance fall due.
 * Stored in `appliance_people.rate_type`; energy service sales have none.
 */
enum RateType: string {
    /** One installment per week */
    case Weekly = 'weekly';
    /** One installment per calendar month */
    case Monthly = 'monthly';

    public function unit(): string {
        return match ($this) {
            self::Weekly => 'week',
            self::Monthly => 'month',
        };
    }

    /**
     * The days one installment covers when it falls due on $date. A calendar month
     * runs 28 to 31 days, so the length depends on where the period starts.
     */
    public function periodDaysFrom(CarbonInterface $date): int {
        return (int) $date->diffInDays($date->toImmutable()->addUnit($this->unit()));
    }
}
