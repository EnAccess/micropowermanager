<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserCreatedEvent;
use App\Exceptions\OwnerEmailAlreadyExistsException;
use App\Helpers\MailHelper;
use App\Services\CompanyDatabaseService;
use App\Services\CompanyService;
use App\Services\DatabaseProxyService;
use App\Services\TicketUserService;
use Illuminate\Database\UniqueConstraintViolationException;

class UserListener {
    public function __construct(
        private DatabaseProxyService $databaseProxyService,
        private CompanyDatabaseService $companyDatabaseService,
        private TicketUserService $ticketUserService,
        private CompanyService $companyService,
        private MailHelper $mailHelper,
    ) {}

    public function handle(UserCreatedEvent $event): void {
        $this->handleUserCreatedEvent($event);
    }

    public function handleUserCreatedEvent(UserCreatedEvent $event): void {
        if ($event->shouldSyncUser) {
            $companyDatabase = $this->companyDatabaseService->findByCompanyId($event->user->company_id);

            $databaseProxyData = [
                'email' => $event->user->email,
                'fk_company_id' => $event->user->company_id,
                'fk_company_database_id' => $companyDatabase->id,
            ];
            // check if owner account email already exists in db proxy
            try {
                $this->databaseProxyService->create($databaseProxyData);
            } catch (UniqueConstraintViolationException) {
                throw new OwnerEmailAlreadyExistsException('Owner account email already exists');
            }
        }

        $company = $this->companyService->getById($event->user->company_id);
        $this->ticketUserService->findOrCreateByUser($event->user);

        // Only send welcome email if the email address is valid
        if (filter_var($event->user->email, FILTER_VALIDATE_EMAIL)) {
            $this->mailHelper->sendViaTemplate(
                $event->user->email,
                'Welcome to MicroPowerManager',
                'templates.mail.register_welcome',
                ['userName' => $event->user->name, 'companyName' => $company->name]
            );
        }
    }
}
