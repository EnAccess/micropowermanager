<?php

namespace Inensus\SteamaMeter\Models;

abstract class SyncStatus
{
    const SYNCED = 1;
    const MODIFIED = 2;
    const NOT_REGISTERED_YET = 3;
}
