<?php

declare(strict_types=1);

namespace MPM\User;

use App\Helpers\MailHelper;
use App\Jobs\EmailJobPayload;
use App\Jobs\SendEmail;
use App\Services\CompanyDatabaseService;
use App\Services\CompanyService;
use App\Services\DatabaseProxyService;
use Inensus\Ticket\Services\TicketUserService;
use MPM\User\Events\UserCreatedEvent;

class UserListener {
    public function __construct(
        private DatabaseProxyService $databaseProxyService,
        private CompanyDatabaseService $companyDatabaseService,
        private TicketUserService $ticketUserService,
        private CompanyService $companyService,
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

            $this->databaseProxyService->create($databaseProxyData);
        }

        $company = $this->companyService->getById($event->user->getCompanyId());
        $this->ticketUserService->findOrCreateByUser($event->user);

        SendEmail::dispatch(
            $event->user->getCompanyId(),
            EmailJobPayload::fromArray(
                [
                    'to' => $event->user->getEmail(),
                    'subject' => 'Welcome to MicroPowerManager',
                    'templatePath' => 'templates.mail.register_welcome',
                    'templateVariables' => [
                        'userName' => $event->user->getName(),
                        'companyName' => $company->getName(),
                    ],
                ]
            )
        );
    }
}
