<?php

namespace App\Services\ImportServices;

final readonly class MeterTypeItem {
    public function __construct(
        public bool $online,
        public int $phase,
        public float $maxCurrent,
    ) {}
}
