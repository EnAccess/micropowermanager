<?php

namespace Inensus\SparkMeter\Helpers;

use Inensus\SparkMeter\Exceptions\SparkAPIResponseException;

class ResultStatusChecker {
    public function checkApiResult(array $result): array {
        throw_if($result['error'] !== false && $result['error'] !== null, new SparkAPIResponseException($result['error']));

        return $result;
    }
}
