<?php

namespace App\Plugins\SmsTransactionParser\Providers;

use App\Lib\IMpmPluginProvider;
use App\Plugins\SmsTransactionParser\Console\Commands\InstallPackage;
use App\Plugins\SmsTransactionParser\Models\SmsTransaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class SmsTransactionParserServiceProvider extends ServiceProvider implements IMpmPluginProvider {
    public const string SLUG = 'sms_transaction_parser';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'SMS Transaction Parser';
    }

    public function description(): string {
        return 'Parse incoming SMS messages from mobile money providers to create payment transactions';
    }

    public function usageType(): string {
        return 'general';
    }

    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
        Relation::morphMap([
            SmsTransaction::RELATION_NAME => SmsTransaction::class,
        ]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(SmsTransactionProvider::class);
        $this->app->alias(SmsTransactionProvider::class, 'SmsTransactionParser');
    }
}
