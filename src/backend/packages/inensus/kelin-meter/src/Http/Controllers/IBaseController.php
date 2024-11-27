<?php

namespace Inensus\KelinMeter\Http\Controllers;

interface IBaseController {
    public function sync();

    public function checkSync();
}
