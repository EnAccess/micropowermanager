<?php

declare(strict_types=1);

namespace App\Plugins\PaystackPaymentProvider\Providers;

use App\Lib\IMpmPluginProvider;
use App\Plugins\PaystackPaymentProvider\Console\Commands\InstallPackage;
use App\Plugins\PaystackPaymentProvider\Models\PaystackTransaction;
use App\Plugins\PaystackPaymentProvider\Services\PaystackCompanyHashService;
use App\Plugins\PaystackPaymentProvider\Services\PaystackCredentialService;
use App\Plugins\PaystackPaymentProvider\Services\PaystackTransactionService;
use App\Plugins\PaystackPaymentProvider\Services\PaystackWebhookService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class PaystackPaymentProviderServiceProvider extends ServiceProvider implements IMpmPluginProvider {
    public const string SLUG = 'paystack_payment_provider';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Paystack';
    }

    public function description(): string {
        return 'This plugin developed to payment via paystack payment provider';
    }

    public function usageType(): string {
        return 'general';
    }

    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([
            InstallPackage::class,
        ]);

        // Register morph map for PaystackTransaction
        Relation::morphMap([
            PaystackTransaction::RELATION_NAME => PaystackTransaction::class,
        ]);

        // Register services
        $this->app->singleton(PaystackCredentialService::class);
        $this->app->singleton(PaystackWebhookService::class);
        $this->app->singleton(PaystackTransactionService::class);
        $this->app->singleton(PaystackCompanyHashService::class);
        $this->app->singleton('PaystackPaymentProvider', PaystackTransactionProvider::class);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
