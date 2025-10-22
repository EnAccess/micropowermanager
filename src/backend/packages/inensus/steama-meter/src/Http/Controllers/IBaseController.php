<?php

namespace Inensus\SteamaMeter\Http\Controllers;

interface IBaseController {
    public function sync(): mixed;

    public function checkSync(): mixed;
}
