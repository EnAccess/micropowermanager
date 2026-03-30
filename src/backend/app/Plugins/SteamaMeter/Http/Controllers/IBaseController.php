<?php

namespace App\Plugins\SteamaMeter\Http\Controllers;

interface IBaseController {
    public function sync(): mixed;

    public function checkSync(): mixed;
}
