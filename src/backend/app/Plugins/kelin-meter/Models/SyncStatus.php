<?php

namespace Inensus\KelinMeter\Models;

abstract class SyncStatus {
    public const SYNCED = 1;
    public const MODIFIED = 2;
    public const NOT_REGISTERED_YET = 3;
    public const EARLY_REGISTERED = 4;
}
