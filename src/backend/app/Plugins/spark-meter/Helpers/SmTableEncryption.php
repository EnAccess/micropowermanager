<?php

namespace Inensus\SparkMeter\Helpers;

class SmTableEncryption {
    /**
     * @param array<string|int, mixed> $data
     */
    public function makeHash(array $data): string {
        return md5(implode('', $data));
    }
}
