<?php

namespace App\Services;

use App\Models\DatabaseProxy;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

abstract class AbstractDashboardCacheDataService {
    public function __construct(protected string $cacheDataKey) {}

    /**
     * @param array<int, string> $dateRange
     */
    abstract public function setData(array $dateRange = []): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getData(): array {
        return Cache::get(self::cacheKeyGenerator()) ? Cache::get(self::cacheKeyGenerator())->toArray() : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDataById(int|string $id): ?array {
        /** @var array<int, array<string, mixed>>|null $cachedData */
        $cachedData = Cache::get(self::cacheKeyGenerator());

        return $cachedData ? collect($cachedData)->filter(fn (array $data): bool => $data['id'] == $id)->first() : null;
    }

    protected function cacheKeyGenerator(): string {
        $user = User::query()->first();
        $databaseProxy = app()->make(DatabaseProxy::class);
        $companyId = $databaseProxy->findByEmail($user->email)->getCompanyId();

        return $this->cacheDataKey.'-'.$companyId;
    }

    protected function reformatPeriod(string $period): string {
        return substr_replace($period, '-', 4, 0);
    }
}
