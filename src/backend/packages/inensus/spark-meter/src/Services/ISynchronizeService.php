<?php

namespace Inensus\SparkMeter\Services;

interface ISynchronizeService {
    public function sync();

    public function syncCheck();

    public function syncCheckBySite($siteId);

    public function modelHasher($model, ...$params): string;
}
