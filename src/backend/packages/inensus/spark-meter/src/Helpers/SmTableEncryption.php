<?php

namespace Inensus\SparkMeter\Helpers;

class SmTableEncryption {
    /**
     * @param array<string, string> $data
     */
    public function makeHash(array $data): string {
        return md5(implode('', $data));
    }
}
