<?php

namespace Inensus\SparkMeter\Helpers;

use Inensus\SparkMeter\Exceptions\SparkAPIResponseException;

class ResultStatusChecker {
    public function checkApiResult($result) {
        if ($result['error'] !== false && $result['error'] !== null) {
            throw new SparkAPIResponseException($result['error']);
        }

        return $result;
    }
}
