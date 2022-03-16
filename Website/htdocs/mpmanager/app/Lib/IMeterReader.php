<?php

namespace App\Lib;

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 09.10.18
 * Time: 10:38
 */
interface IMeterReader
{
    /**
     * Reads the data for a single meter
     *
     * @param  $meterIdentifier
     * @param  int $type            defines what to read from the remote api
     * @return mixed
     */
    public function readMeter($meterIdentifier, $type);

    /**
     * Reads the data for a given meter list
     *
     * @param  $meterList
     * @param  int $type      defines what to read from the remote api
     *                        * @param array $options
     * @return mixed
     */
    public function readBatch($meterList, $type, $options);
}
