<?php

declare(strict_types=1);

namespace MPM\User;

use App\Services\CompanyDatabaseService;
use App\Services\DatabaseProxyService;
use Inensus\Ticket\Services\TicketUserService;
use MPM\User\Events\UserCreatedEvent;

class UserListener
{
    public function __construct(private DatabaseProxyService $databaseProxyService, private CompanyDatabaseService $companyDatabaseService, private TicketUserService $ticketUserService)
    {
    }

    public function handle($event): void
    {
        if ($event instanceof UserCreatedEvent) {
            $this->handleUserCreatedEvent($event);
        }
    }


    public function handleUserCreatedEvent(UserCreatedEvent $event)
    {
        if($event->shouldSyncUser) {
            $companyDatabase = $this->companyDatabaseService->findByCompanyId($event->user->getCompanyId());

            $databaseProxyData = [
                'email' => $event->user->getEmail(),
                'fk_company_id' => $event->user->getCompanyId(),
                'fk_company_database_id' => $companyDatabase->getId()
            ];

            $this->databaseProxyService->create($databaseProxyData);
        }

        $this->ticketUserService->findOrCreateByUser($event->user);
    }


}
