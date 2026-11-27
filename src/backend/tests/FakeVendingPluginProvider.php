<?php

namespace Tests;

use App\Lib\IVendingPluginProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class FakeVendingPluginProvider extends ServiceProvider implements IVendingPluginProvider {
    public function __construct(
        Application $app,
        private string $slug,
        private string $tokenIssuer,
    ) {
        parent::__construct($app);
    }

    public function slug(): string {
        return $this->slug;
    }

    public function displayName(): string {
        return "Fake {$this->slug}";
    }

    public function description(): string {
        return "Vends through {$this->tokenIssuer}";
    }

    public function usageType(): string {
        return 'general';
    }

    public function tokenIssuer(): string {
        return $this->tokenIssuer;
    }
}
