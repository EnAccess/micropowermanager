<?php

namespace App\Console\Commands;

use App\Services\UserService;
use Illuminate\Console\Command;

class AdminPasswordResetter extends Command
{

    private $userService;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:admin-password';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset forgotten password';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(UserService $userService)
    {
        parent::__construct();
        $this->userService = $userService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $admin = $this->userService->resetAdminPassword();
        $this->alert('
        Please use following credentials to login:
        Email = ' . $admin['email'] . '
        Password = ' . $admin['password']);
    }
}
