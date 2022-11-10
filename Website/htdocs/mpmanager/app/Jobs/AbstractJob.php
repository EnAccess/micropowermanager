<?php

namespace App\Jobs;

use App\Http\Middleware\UserDefaultDatabaseConnectionMiddleware;
use App\Models\User;
use App\Services\CompanyService;
use App\Services\UserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AbstractJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected int $companyId;

    public function __construct(private CompanyService $companyService)
    {
    }


    public function middleware(): array
    {
        return [UserDefaultDatabaseConnectionMiddleware::class];
    }

    public function getCompanyId(): int
    {
        if (!$this->companyId) {
            $companyName = config('database.connections.shard.database');
            $company = $this->companyService->getByName($companyName);

            return $company->id;
        }
        return $this->companyId;
    }


}
