<?php

namespace App\Jobs;

use App\Http\Middleware\UserDefaultDatabaseConnectionMiddleware;
use App\Models\User;
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


    public function middleware(): array
    {
        return [UserDefaultDatabaseConnectionMiddleware::class];
    }

    public function getCompanyId(): int
    {
        return (app()->make(UserService::class))->getCompanyId();
    }


}
