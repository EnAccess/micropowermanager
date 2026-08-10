<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\EBike;
use App\Models\Meter\Meter;
use App\Models\SolarHomeSystem;

// The docblocks below are public and get rendered into the API docs.

/**
 * The kind of unit a `Device` record wraps.
 * Stored in `devices.device_type` and used as the morph name of the device relation,
 * so the values are derived from the models' `RELATION_NAME` constants.
 */
enum DeviceType: string {
    /** Smart Meter */
    case Meter = Meter::RELATION_NAME;
    /** Solar Home System (SHS) */
    case SolarHomeSystem = SolarHomeSystem::RELATION_NAME;
    /** E-Bike */
    case EBike = EBike::RELATION_NAME;

    /**
     * The credit a token for this kind of unit carries. Manufacturer APIs hard-code
     * it per device kind — meters vend energy, the battery-powered units vend time —
     * so it cannot be read off the API and follows from the device type instead.
     */
    public function creditUnit(): DeviceTokenUnit {
        return match ($this) {
            self::Meter => DeviceTokenUnit::Kwh,
            self::SolarHomeSystem, self::EBike => DeviceTokenUnit::Days,
        };
    }
}
