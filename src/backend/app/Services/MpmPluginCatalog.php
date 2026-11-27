<?php

namespace App\Services;

use App\Lib\IMpmPluginProvider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Every plugin shipped with MicroPowerManager, as described by its main service provider.
 */
class MpmPluginCatalog {
    /**
     * @return array<string, IMpmPluginProvider> keyed by slug
     */
    public function all(): array {
        /** @var array<string, IMpmPluginProvider> $providers */
        $providers = app()->getProviders(IMpmPluginProvider::class);

        return collect($providers)
            ->keyBy(fn (IMpmPluginProvider $provider): string => $provider->slug())
            ->all();
    }

    /**
     * @return list<string>
     */
    public function slugs(): array {
        return array_keys($this->all());
    }

    public function find(string $slug): ?IMpmPluginProvider {
        return $this->all()[$slug] ?? null;
    }

    public function get(string $slug): IMpmPluginProvider {
        return $this->find($slug) ?? throw new NotFoundHttpException("Plugin {$slug} not found.");
    }

    public function displayName(string $slug): string {
        return $this->find($slug)?->displayName() ?? $slug;
    }

    /**
     * Every plugin registers its install command as its slug in kebab-case.
     */
    public function installationCommand(string $slug): string {
        return str_replace('_', '-', $slug).':install';
    }
}
