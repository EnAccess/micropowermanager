<?php

namespace Inensus\SwiftaPaymentProvider\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Inensus\SwiftaPaymentProvider\Models\SwiftaAuthentication;
use Tymon\JWTAuth\Facades\JWTAuth;

class UpdatePackage extends Command {
    protected $signature = 'swifta-payment-provider:update';
    protected $description = 'Update the Swifta Payment Provider Integration Package';

    public function __construct(
        private User $user,
        private Filesystem $filesystem,
        private SwiftaAuthentication $authentication,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void {
        $this->info('Swifta Payment Provider Integration Updating Started\n');
        $this->removeOldVersionOfPackage();
        $this->installNewVersionOfPackage();
        $this->deleteMigration($this->filesystem);
        $this->publishConfigurations();
        $this->publishMigrationsAgain();
        $this->updateDatabase();
        $token = $this->generateAuthenticationTokenAgain();
        $this->warn("Authentication token for swifta payments generated again. token =>\n {$token}");
        $this->info('Package updated successfully..');
    }

    private function publishConfigurations() {
        $this->info('Copying configurations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\SwiftaPaymentProvider\Providers\SwiftaServiceProvider",
            '--tag' => 'configurations',
        ]);
    }

    private function removeOldVersionOfPackage() {
        $this->info('Removing former version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/swifta-payment-provider');
    }

    private function installNewVersionOfPackage() {
        $this->info('Installing last version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  require inensus/swifta-payment-provider');
    }

    private function deleteMigration(Filesystem $filesystem) {
        $migrationFile = $filesystem->glob(database_path().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'*_create_swifta_payment_provider_tables.php')[0];
        $migration = DB::table('migrations')
            ->where('migration', substr(explode('/migrations/', $migrationFile)[1], 0, -4))->first();
        if (!$migration) {
            return;
        }
        DB::table('migrations')
            ->where('migration', substr(explode('/migrations/', $migrationFile)[1], 0, -4))->delete();
    }

    private function publishMigrationsAgain() {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\SwiftaPaymentProvider\Providers\SwiftaServiceProvider",
            '--tag' => 'migrations',
        ]);
    }

    private function updateDatabase() {
        $this->info('Updating database tables\n');
        $this->call('migrate');
    }

    private function generateAuthenticationTokenAgain() {
        $password = $this->generateRandomNumber();
        $user = $this->user->newQuery()->firstOrCreate([
            'email' => 'swifta-user',
        ], [
            'name' => 'swifta-user',
            'password' => $password,
            'email' => 'swifta-user',
        ]);

        $customClaims = ['usr' => 'swifta-token', 'exp' => Carbon::now()->addYears(1)->timestamp];
        $token = JWTAuth::customClaims($customClaims)->fromUser($user);
        $payload = JWTAuth::setToken($token)->getPayload();
        $expirationTime = $payload['exp'];
        $this->authentication->newQuery()->updateOrCreate(['id' => 1], [
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
