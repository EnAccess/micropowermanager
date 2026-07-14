<?php

declare(strict_types=1);

namespace App\Enums;

// The docblocks below are public and get rendered into the API docs.

/**
 * Outcome of the last manufacturer mapping check of a device.
 */
enum ManufacturerMappingStatus: string {
    /** Mapping has never been checked, or the last check errored. */
    case Unknown = 'unknown';
    /** The unit is known/mapped on the manufacturer side. */
    case Mapped = 'mapped';
    /** The manufacturer API responded that the unit is not mapped. */
    case NotMapped = 'not_mapped';
    /** The device's manufacturer exposes no device management API. */
    case Unsupported = 'unsupported';
}
