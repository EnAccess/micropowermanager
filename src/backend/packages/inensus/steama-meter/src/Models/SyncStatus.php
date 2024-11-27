<?php

namespace Inensus\SteamaMeter\Models;

abstract class SyncStatus {
    public const SYNCED = 1;
    public const MODIFIED = 2;
    public const NOT_REGISTERED_YET = 3;
}
