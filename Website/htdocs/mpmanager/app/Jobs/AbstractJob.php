<?php

namespace App\Jobs;

use App\Http\Middleware\UserDefaultDatabaseConnectionMiddleware;
use App\Models\CompanyJob;
use App\Models\User;
use App\Services\CompanyService;
use App\Services\UserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

abstract class AbstractJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected int $companyId;
    protected CompanyJob $companyJob;

    abstract public function executeJob();

    public function __construct($jobName)
    {
        $this->companyId = app()->make(UserService::class)->getCompanyId();
        $this->companyJob = CompanyJob::query()->create([
            'company_id' => $this->companyId,
            'status' => CompanyJob::STATUS_PENDING,
            'job_name' => $jobName,
            'job_id' => null
        ]);


    }

    public function handle()
    {
        $this->setJobUuid($this->job->uuid());
        $databaseProxyManager = app()->make(DatabaseProxyManagerService::class);
        $databaseProxyManager->runForCompany($this->companyId, function () {

            $this->executeJob();
        });

        $this->setJobStatus(CompanyJob::STATUS_SUCCESS);
    }

    public function failed()
    {
        $this->setJobStatus(CompanyJob::STATUS_FAILED);
    }

    protected function setJobUuid($jobUuid)
    {
        $this->companyJob->job_uuid = $jobUuid;
        $this->companyJob->save();
    }

    protected function setJobStatus($status)
    {
        $this->companyJob->status = $status;
        $this->companyJob->save();
    }
}
