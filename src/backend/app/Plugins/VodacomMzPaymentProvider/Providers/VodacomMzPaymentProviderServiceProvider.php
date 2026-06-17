<?php

namespace App\Plugins\VodacomMzPaymentProvider\Providers;

use App\Plugins\VodacomMzPaymentProvider\Console\Commands\InstallPackage;
use App\Plugins\VodacomMzPaymentProvider\Models\VodacomMzTransaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class VodacomMzPaymentProviderServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);

        // Register morph map for PaystackTransaction
        Relation::morphMap([
            VodacomMzTransaction::RELATION_NAME => VodacomMzTransaction::class,
        ]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
