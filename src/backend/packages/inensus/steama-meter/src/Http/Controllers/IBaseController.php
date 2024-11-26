<?php

namespace Inensus\SteamaMeter\Http\Controllers;

interface IBaseController {
    public function sync();

    public function checkSync();
}
