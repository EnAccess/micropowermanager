<?php

namespace Inensus\KelinMeter\Http\Controllers;

interface IBaseController {
    public function sync(): mixed;

    public function checkSync(): mixed;
}
