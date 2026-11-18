<?php

namespace Tests;

use App\Lib\IVendingPluginProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class FakeVendingPluginProvider extends ServiceProvider implements IVendingPluginProvider {
    public function __construct(
        Application $app,
        private int $mpmPluginId,
        private string $tokenIssuer,
    ) {
        parent::__construct($app);
    }

    public function mpmPluginId(): int {
        return $this->mpmPluginId;
    }

    public function tokenIssuer(): string {
        return $this->tokenIssuer;
    }
}
