<?php

namespace Inensus\SparkMeter\Http\Controllers;

interface IBaseController {
    public function sync();

    public function checkSync();
}
