<?php

namespace Inensus\SparkMeter\Services;

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

    // @phpstan-ignore missingType.iterableValue
    public function syncCheckBySite(string $siteId): array;

    // @phpstan-ignore missingType.iterableValue
    public function modelHasher(array $model, ?string ...$params): string;
}
