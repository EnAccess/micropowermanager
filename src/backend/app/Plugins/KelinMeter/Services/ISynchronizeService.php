<?php

namespace App\Plugins\KelinMeter\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @template T of Model
 */
interface ISynchronizeService {
    /**
     * @return LengthAwarePaginator<int, T>
     */
    public function sync(): LengthAwarePaginator;

    // @phpstan-ignore missingType.iterableValue
    public function syncCheck(bool $returnData): array;
}
