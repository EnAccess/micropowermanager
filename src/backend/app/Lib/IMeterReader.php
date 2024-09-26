<?php

namespace App\Lib;

interface IMeterReader
{
    /**
     * Reads the data for a single meter.
     *
     * @param     $meterIdentifier
     * @param int $type            defines what to read from the remote api
     *
     * @return mixed
     */
    public function readMeter($meterIdentifier, $type);

    /**
     * Reads the data for a given meter list.
     *
     * @param     $meterList
     * @param int $type      defines what to read from the remote api
     *                       * @param array $options
     *
     * @return mixed
     */
    public function readBatch($meterList, $type, $options);
}
