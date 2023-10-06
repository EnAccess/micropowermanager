<?php

namespace App\Services;

use App\Models\City;
use App\Models\ConnectionGroup;
use App\Models\DatabaseProxy;
use App\Models\Revenue;
use App\Models\Target;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inensus\Ticket\Models\Ticket;
use Inensus\Ticket\Models\TicketCategory;

abstract class AbstractDashboardCacheDataService
{
    protected $cacheDataKey;

    public function __construct(
        string $cacheDataKey
    ) {
        $this->cacheDataKey = $cacheDataKey;
    }

    public abstract function setData($dateRange = []);

    public function getData()
    {
        return Cache::get(self::cacheKeyGenerator()) ? Cache::get(self::cacheKeyGenerator())->toArray() : [];
    }

    public function getDataById($id)
    {
        $cachedData = Cache::get(self::cacheKeyGenerator());
        return $cachedData ? collect($cachedData)->filter(function ($data) use ($id) {
            return $data['id'] == $id;
        })->first() : [];
    }

    protected function cacheKeyGenerator()
    {
        $user = User::query()->first();
        $databaseProxy = app()->make(DatabaseProxy::class);
        $companyId = $databaseProxy->findByEmail($user->email)->getCompanyId();
        return $this->cacheDataKey . '-' . $companyId;
    }

    protected function reformatPeriod($period): string
    {
        return substr_replace($period, '-', 4, 0);
    }


}