<?php

namespace App\Console\Commands;

use App\Services\UserService;
use Illuminate\Console\Command;

class AdminPasswordResetter extends AbstractSharedCommand
{

    protected $signature = 'reset:admin-password';
    protected $description = 'Reset forgotten password';

    public function __construct(private UserService $userService)
    {
        parent::__construct();
    }
    function runInCompanyScope(): void
    {
        $admin = $this->userService->resetAdminPassword();
        $this->alert('
        Please use following credentials to login:
        Email = ' . $admin['email'] . '
        Password = ' . $admin['password']);
    }
}
