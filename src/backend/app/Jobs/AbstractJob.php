<?php

namespace App\Jobs;

use App\Models\CompanyJob;
use App\Services\UserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

abstract class AbstractJob implements ShouldQueue {
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected int $companyId;
    protected CompanyJob $companyJob;
    public ?string $parentId = null;

    /**
     * Execute the job logic. Child classes must implement this.
     */
    abstract public function executeJob(): void;

    /**
     * AbstractJob constructor.
     */
    public function __construct(string $jobName) {
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

    public function handle(): void {
        $this->setJobUuid($this->job->uuid());

        $databaseProxyManager = app()->make(DatabaseProxyManagerService::class);
        $databaseProxyManager->runForCompany($this->companyId, function (): void {
            $this->executeJob();
        });

        $this->setJobStatus(CompanyJob::STATUS_SUCCESS);
    }

    public function failed(?\Throwable $t = null): void {
        $trace = $t !== null ? explode('#15', $t->getTraceAsString(), 1000)[0] : null;
        $this->setJobStatus(CompanyJob::STATUS_FAILED, $t?->getMessage(), $trace);
    }

    protected function setJobUuid(string $jobUuid): void {
        $this->companyJob->job_uuid = $jobUuid;
        $this->companyJob->save();
    }

    protected function setJobStatus(int $status, ?string $message = null, ?string $trace = null): void {
        $this->companyJob->status = $status;
        $this->companyJob->message = $message;
        $this->companyJob->trace = $trace;
        $this->companyJob->save();
    }
}
