<?php

namespace App\Jobs;

use App\Models\CompanyJob;
use App\Services\CompanyService;
use App\Services\UserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class AbstractJob implements ShouldQueue {
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected int $companyId;
    protected CompanyJob $companyJob;

    public ?string $parentId = null;

    abstract public function executeJob();

    public function __construct($jobName) {
        $this->afterCommit = true;
        $this->companyId = app()->make(UserService::class)->getCompanyId();

        $this->companyJob = CompanyJob::query()->create(
            [
                'company_id' => $this->companyId,
                'status' => CompanyJob::STATUS_PENDING,
                'job_name' => $jobName,
                'job_id' => $this->parentId,
            ]
        );
    }

    public function handle() {
        $this->setJobUuid($this->job->uuid());
        $companyService = app()->make(CompanyService::class);
        $companyService->runForCompany($this->companyId, function () {
            $this->executeJob();
        });

        $this->setJobStatus(CompanyJob::STATUS_SUCCESS);
    }

    public function failed(?\Throwable $t = null) {
        $trace = $t !== null ? explode('#15', $t->getTraceAsString(), 1000)[0] : null;
        $this->setJobStatus(CompanyJob::STATUS_FAILED, $t?->getMessage(), $trace);
    }

    protected function setJobUuid($jobUuid) {
        $this->companyJob->job_uuid = $jobUuid;
        $this->companyJob->save();
    }

    protected function setJobStatus($status, ?string $message = null, ?string $trace = null) {
        $this->companyJob->status = $status;
        $this->companyJob->message = $message;
        $this->companyJob->trace = $trace;
        $this->companyJob->save();
    }
}
