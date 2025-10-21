<?php

namespace Inensus\SparkMeter\Helpers;

use Inensus\SparkMeter\Exceptions\SparkAPIResponseException;

class ResultStatusChecker {
    /**
     * @param array<string, mixed>|string $result
     *
     * @return array<string, mixed>|string
     */
    public function checkApiResult(array|string $result): array|string {
        if ($result['error'] !== false && $result['error'] !== null) {
            throw new SparkAPIResponseException($result['error']);
        }

        return $result;
    }
}
