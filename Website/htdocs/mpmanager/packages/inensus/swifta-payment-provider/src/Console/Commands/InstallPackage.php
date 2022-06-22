<?php

namespace Inensus\SwiftaPaymentProvider\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

use Inensus\SwiftaPaymentProvider\Models\SwiftaAuthentication;
use Tymon\JWTAuth\Facades\JWTAuth;

class InstallPackage extends Command
{
    protected $signature = 'swifta-payment-provider:install';
    protected $description = 'Install SwiftaPaymentProvider Package';
    private $user;
    private $authentication;

    public function __construct(User $user, SwiftaAuthentication $authentication)
    {
        parent::__construct();
        $this->user = $user;
        $this->authentication = $authentication;
    }

    public function handle(): void
    {
        $this->info('Installing SwiftaPaymentProvider Integration Package\n');
        $this->publishConfigurations();
        $this->publishMigrations();
        $this->createDatabaseTables();
        $this->createPluginRecord();
        $token = $this->generateAuthenticationToken();
        $this->warn("Authentication token for swifta payments generated. token =>\n {$token}");
        $this->info('Package installed successfully..');
    }

    private function publishConfigurations()
    {
        $this->info('Copying configurations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\SwiftaPaymentProvider\Providers\SwiftaServiceProvider",
            '--tag' => "configurations",
        ]);
    }

    private function publishMigrations()
    {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\SwiftaPaymentProvider\Providers\SwiftaServiceProvider",
            '--tag' => "migrations"
        ]);
    }

    private function createDatabaseTables()
    {
        $this->info('Creating database tables\n');
        $this->call('migrate');
    }

    private function createPluginRecord()
    {
        $this->call('plugin:add', [
            'name' => "SwiftaPaymentProvider",
            'composer_name' => "inensus/swifta-payment-provider",
            'description' => "SwiftaPaymentProvider integration package for MicroPowerManager",
        ]);
    }

    private function generateAuthenticationToken()
    {

        $password = $this->generateRandomNumber();
        $user = $this->user->newQuery()->create([
            'name' => 'swifta-user',
            'password' => $password,
            'email' => 'swifta-user'
        ]);

        $customClaims = ['usr' => 'swifta-token', 'exp' => Carbon::now()->addYears(1)->timestamp];
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
}