<?php

namespace App\Lib;

interface IMeterReader {
    /**
     * Reads the data for a single meter.
     *
     * @param string $meterIdentifier
     * @param int    $type            defines what to read from the remote api
     *
     * @return mixed
     */
    public function readMeter(string $meterIdentifier, int $type): mixed;

    /**
     * Reads the data for a given meter list.
     *
     * @param array<string>        $meterList
     * @param int                  $type      defines what to read from the remote api
     * @param array<string, mixed> $options
     *
     * @return mixed
     */
    public function readBatch(array $meterList, int $type, array $options): mixed;
}
