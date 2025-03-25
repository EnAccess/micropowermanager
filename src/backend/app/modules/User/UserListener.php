<?php

declare(strict_types=1);

namespace MPM\User;

use App\Helpers\MailHelperInterface;
use App\Services\CompanyDatabaseService;
use App\Services\CompanyService;
use Inensus\Ticket\Services\TicketUserService;
use MPM\User\Events\UserCreatedEvent;

class UserListener {
    public function __construct(
        private CompanyDatabaseService $companyDatabaseService,
        private TicketUserService $ticketUserService,
        private CompanyService $companyService,
        private MailHelperInterface $mailHelper,
    ) {}

    public function handle($event): void {
        if ($event instanceof UserCreatedEvent) {
            $this->handleUserCreatedEvent($event);
        }
    }

    public function handleUserCreatedEvent(UserCreatedEvent $event): void {
        $company = $this->companyService->getById($event->user->getCompanyId());
        $this->ticketUserService->findOrCreateByUser($event->user);

        $this->mailHelper->sendViaTemplate(
            $event->user->getEmail(),
            'Welcome to MicroPowerManager',
            'templates.mail.register_welcome',
            ['userName' => $event->user->getName(), 'companyName' => $company->getName()]
        );
    }
}
