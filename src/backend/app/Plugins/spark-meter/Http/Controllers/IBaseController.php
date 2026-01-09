<?php

namespace Inensus\SparkMeter\Http\Controllers;

interface IBaseController {
    public function sync(): mixed;

    public function checkSync(): mixed;
}
