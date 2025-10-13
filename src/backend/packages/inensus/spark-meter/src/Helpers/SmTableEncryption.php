<?php

namespace Inensus\SparkMeter\Helpers;

class SmTableEncryption {
    public function makeHash($data): string {
        return md5(implode('', $data));
    }
}
