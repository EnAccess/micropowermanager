<?php

namespace Inensus\SparkMeter\Http\Controllers;

use Illuminate\Routing\Controller;

interface IBaseController
{
    public function sync();
    public function checkSync();
}
