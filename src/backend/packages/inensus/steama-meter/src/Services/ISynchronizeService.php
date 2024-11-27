<?php

namespace Inensus\SteamaMeter\Services;

interface ISynchronizeService {
    public function sync();

    public function syncCheck();
}
