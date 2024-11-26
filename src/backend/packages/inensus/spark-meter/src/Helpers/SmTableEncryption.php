<?php

namespace Inensus\SparkMeter\Helpers;

class SmTableEncryption {
    public function makeHash($data) {
        return md5(implode('', $data));
    }
}
