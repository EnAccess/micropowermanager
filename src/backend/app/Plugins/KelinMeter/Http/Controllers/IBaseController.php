<?php

namespace App\Plugins\KelinMeter\Http\Controllers;

interface IBaseController {
    public function sync(): mixed;

    public function checkSync(): mixed;
}
