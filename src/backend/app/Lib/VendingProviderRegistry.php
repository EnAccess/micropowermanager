<?php

declare(strict_types=1);

namespace App\Lib;

use App\Models\Manufacturer;
use App\Services\PluginsService;

/**
 * A vending provider issues tokens for meters it did not make, such as native STS
 * vending a Calin meter, so a meter has to be able to name one apart from its
 * manufacturer. Each provider declares the manufacturers it can vend for, and a
 * meter may only name a provider that supports its own manufacturer.
 */
class VendingProviderRegistry {
    /** @var array<string, VendingProvider> */
    private array $providers = [];

    public function __construct(
        private PluginsService $pluginsService,
    ) {}

    public function register(VendingProvider $provider): void {
        $this->providers[$provider->key] = $provider;
    }

    public function supports(string $key, ?Manufacturer $manufacturer): bool {
        $provider = $this->providers[$key] ?? null;

        return $provider instanceof VendingProvider
            && $manufacturer instanceof Manufacturer
            && in_array($manufacturer->api_name, $provider->manufacturerApiNames, true);
    }

    public function isActive(string $key): bool {
        $provider = $this->providers[$key] ?? null;

        return $provider instanceof VendingProvider && $this->pluginsService->isPluginActive($provider->mpmPluginId);
    }

    public function make(string $key): IVendingProvider {
        $provider = $this->providers[$key] ?? throw new \InvalidArgumentException("No vending provider is registered as {$key}.");

        return resolve($provider->apiClass);
    }

    /**
     * @return list<VendingProvider>
     */
    public function availableFor(?Manufacturer $manufacturer): array {
        return array_values(array_filter(
            $this->providers,
            fn (VendingProvider $provider): bool => $this->supports($provider->key, $manufacturer) && $this->isActive($provider->key),
        ));
    }
}
