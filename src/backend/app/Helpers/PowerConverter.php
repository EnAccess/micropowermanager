<?php

namespace App\Helpers;

class PowerConverter {
    /**
     * @var array<string, int>
     */
    private static array $powerUnits = [
        'W' => 1,
        'kW' => 1000,
        'MW' => 1000000,
        'Wh' => 1,
        'kWh' => 1000,
        'MWh' => 1000000,
    ];

    public static function convert(int|string $power, string $powerUnit, string $expectedUnit = 'Wh'): float|int {
        return $power * self::$powerUnits[$powerUnit] / self::$powerUnits[$expectedUnit];
    }
}
