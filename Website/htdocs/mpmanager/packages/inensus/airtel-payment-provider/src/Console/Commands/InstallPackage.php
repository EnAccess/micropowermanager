<?php
namespace Inensus\AirtelPaymentProvider\Console\Commands;

use App\Models\User;
use App\Services\CompanyDatabaseService;
use App\Services\CompanyService;
use App\Services\DatabaseProxyService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Inensus\AirtelPaymentProvider\Models\AirtelAuthentication;
use Inensus\AirtelPaymentProvider\Services\MenuItemService;
use Tymon\JWTAuth\Facades\JWTAuth;

class InstallPackage extends Command
{
    protected $signature = 'airtel-payment-provider:install';
    protected $description = 'Install AirtelPaymentProvider Package';

    public function __construct(
        private User $user,
        private AirtelAuthentication $authentication,
        private CompanyService $companyService,
        private CompanyDatabaseService $companyDatabaseService,
        private DatabaseProxyService $databaseProxyService,
        private MenuItemService $menuItemService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Installing AirtelPaymentProvider Integration Package\n');
        $this->createMenuItems();
        $token = $this->generateAuthenticationToken();
        $this->warn("Authentication token for airtel payments generated. token =>\n {$token}");
        $this->info('Package installed successfully..');
    }

    private function generateAuthenticationToken()
    {
        $password = $this->generateRandomNumber();
        $companyId = app()->make(UserService::class)->getCompanyId();
        $company = $this->companyService->getById($companyId);
        $user = $this->user->newQuery()->firstOrCreate([
            'name' => 'airtel-user',
            'password' => $password,
            'email' => $company->getName() . '-airtel-user-' . Carbon::now()->timestamp,
            'company_id' => $companyId
        ]);
        $companyDatabase = $this->companyDatabaseService->getById($companyId);
        $databaseProxyData = [
            'email' => $user->getEmail(),
            'fk_company_id' => $user->getCompanyId(),
            'fk_company_database_id' => $companyDatabase->getId(),
        ];
        $this->databaseProxyService->create($databaseProxyData);
        $customClaims = ['usr' => 'airtel-token', 'exp' => Carbon::now()->addYears(5)->timestamp];
        $token = JWTAuth::customClaims($customClaims)->fromUser($user);
        $payload = JWTAuth::setToken($token)->getPayload();
        $expirationTime = $payload['exp'];
        $this->authentication->newQuery()->create([
            'token' => $token,
            'expire_date' => $expirationTime
        ]);
        return $token;
    }

    private function generateRandomNumber(): string
    {
        $length = random_int(1, 10);
        $number = '';
        for ($i = 0; $i < $length; $i++) {
            $number .= random_int(0, 9);
        }
        $number = ltrim($number, '0');
        if ($number === '') {
            return '0';
        }
        return $number;
    }

    private function createMenuItems()
    {
        $menuItems = $this->menuItemService->createMenuItems();
        $this->call('menu-items:generate', [
            'menuItem' => $menuItems['menuItem'],
            'subMenuItems' => $menuItems['subMenuItems'],
        ]);
    }
}