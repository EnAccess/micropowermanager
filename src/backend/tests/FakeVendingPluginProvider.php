<?php

namespace Tests;

use App\Enums\MpmPlugin;
use App\Lib\IVendingPluginProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class FakeVendingPluginProvider extends ServiceProvider implements IVendingPluginProvider {
    public function __construct(
        Application $app,
        private MpmPlugin $mpmPlugin,
        private string $tokenIssuer,
    ) {
        parent::__construct($app);
    }

    public function mpmPlugin(): MpmPlugin {
        return $this->mpmPlugin;
    }

    public function tokenIssuer(): string {
        return $this->tokenIssuer;
    }
}
