<?php

namespace App\Lib;

interface IMeterReader {
    /**
     * Reads the data for a single meter.
     *
     * @param int $type defines what to read from the remote API
     */
    public function readMeter(string|int $meterIdentifier, int $type): mixed;

    /**
     * Reads the data for a given meter list.
     *
     * @param array<int, string|int> $meterList
     * @param int                    $type      defines what to read from the remote API
     * @param array<string, mixed>   $options   additional read options
     */
    public function readBatch(array $meterList, int $type, array $options): mixed;
}
