<?php

namespace App\Plugins\SwiftaPaymentProvider\Providers;

use App\Plugins\SwiftaPaymentProvider\Console\Commands\InstallPackage;
use App\Plugins\SwiftaPaymentProvider\Console\Commands\TransactionStatusChecker;
use App\Plugins\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class SwiftaServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class, TransactionStatusChecker::class]);
        Relation::morphMap(
            [
                SwiftaTransaction::RELATION_NAME => SwiftaTransaction::class,
            ]
        );
        $this->app->make(Schedule::class)->command('swifta-payment-provider:transactionStatusCheck')->dailyAt('00:00')
            ->appendOutputTo(storage_path('logs/cron.log'));
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(SwiftaTransactionProvider::class);
        $this->app->alias(SwiftaTransactionProvider::class, 'SwiftPaymentProvider');
    }
}
