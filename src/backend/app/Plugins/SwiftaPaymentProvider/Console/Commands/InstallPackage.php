<?php

namespace App\Plugins\SwiftaPaymentProvider\Console\Commands;

use App\Models\User;
use App\Plugins\SwiftaPaymentProvider\Models\SwiftaAuthentication;
use App\Services\CompanyDatabaseService;
use App\Services\CompanyService;
use App\Services\DatabaseProxyService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Tymon\JWTAuth\JWTGuard;

class InstallPackage extends Command {
    protected $signature = 'swifta-payment-provider:install';
    protected $description = 'Install SwiftaPaymentProvider Package';

    public function __construct(
        private User $user,
        private SwiftaAuthentication $authentication,
        private CompanyService $companyService,
        private CompanyDatabaseService $companyDatabaseService,
        private DatabaseProxyService $databaseProxyService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing SwiftaPaymentProvider Integration Package\n');
        $token = $this->generateAuthenticationToken();
        $this->warn("Authentication token for swifta payments generated. token =>\n {$token}");
        $this->info('Package installed successfully..');
    }

    private function generateAuthenticationToken(): string {
        $password = $this->generateRandomNumber();
        $companyId = app()->make(UserService::class)->getCompanyId();
        $company = $this->companyService->getById($companyId);

        // Generate a valid email address by sanitizing the company name
        $sanitizedCompanyName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $company->getName()));
        $email = 'swifta-user-'.Carbon::now()->timestamp.'@'.$sanitizedCompanyName.'.local';

        /** @var User $user */
        $user = $this->user->newQuery()->firstOrCreate([
            'name' => 'swifta-user',
            'password' => $password,
            'email' => $email,
            'company_id' => $companyId,
        ]);
        $companyDatabase = $this->companyDatabaseService->getById($companyId);
        $databaseProxyData = [
            'email' => $user->getEmail(),
            'fk_company_id' => $user->getCompanyId(),
            'fk_company_database_id' => $companyDatabase->getId(),
        ];
        $this->databaseProxyService->create($databaseProxyData);

        /** @var JWTGuard $guard */
        $guard = auth('api');

        $customClaims = ['usr' => 'swifta-token', 'exp' => Carbon::now()->addYears(3)->timestamp];
        $token = $guard->claims($customClaims)->login($user);
        $payload = $guard->payload();

        $expirationTime = $payload['exp'];
        $this->authentication->newQuery()->create([
            'token' => $token,
            'expire_date' => $expirationTime,
        ]);

        return $token;
    }

    private function generateRandomNumber(): string {
        $length = random_int(1, 10);
        $number = '';
        for ($i = 0; $i < $length; ++$i) {
            $number .= random_int(0, 9);
        }
        $number = ltrim($number, '0');
        if ($number === '') {
            return '0';
        }

        return $number;
    }
}
