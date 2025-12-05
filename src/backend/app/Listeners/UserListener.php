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
            $companyDatabase = $this->companyDatabaseService->findByCompanyId($event->user->getCompanyId());

            $databaseProxyData = [
                'email' => $event->user->getEmail(),
                'fk_company_id' => $event->user->getCompanyId(),
                'fk_company_database_id' => $companyDatabase->getId(),
            ];
            // check if owner account email already exists in db proxy
            try {
                $this->databaseProxyService->create($databaseProxyData);
            } catch (UniqueConstraintViolationException) {
                throw new OwnerEmailAlreadyExistsException('Owner account email already exists');
            }
        }

        $company = $this->companyService->getById($event->user->getCompanyId());
        $this->ticketUserService->findOrCreateByUser($event->user);

        // Only send welcome email if the email address is valid
        if (filter_var($event->user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            $this->mailHelper->sendViaTemplate(
                $event->user->getEmail(),
                'Welcome to MicroPowerManager',
                'templates.mail.register_welcome',
                ['userName' => $event->user->getName(), 'companyName' => $company->getName()]
            );
        }
    }
}
