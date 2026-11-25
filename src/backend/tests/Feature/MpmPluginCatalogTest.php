<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MpmPlugin;
use App\Lib\IVendingPluginProvider;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MpmPluginCatalogTest extends TestCase {
    public function testEveryPluginHasAnInstallationCommand(): void {
        $commands = array_keys(Artisan::all());

        foreach (MpmPlugin::cases() as $mpmPlugin) {
            $this->assertNotSame('', $mpmPlugin->displayName());
            $this->assertNotSame('', $mpmPlugin->description());
            $this->assertContains($mpmPlugin->installationCommand(), $commands, "{$mpmPlugin->value} has no installation command.");
        }
    }

    public function testEveryVendingPluginProviderIsADistinctPlugin(): void {
        $vendingPlugins = array_map(
            fn (IVendingPluginProvider $provider): string => $provider->mpmPlugin()->value,
            array_values($this->app->getProviders(IVendingPluginProvider::class)),
        );

        $this->assertNotEmpty($vendingPlugins);
        $this->assertSame($vendingPlugins, array_unique($vendingPlugins));
    }

    public function testItListsTheCatalog(): void {
        $response = $this->getJson('/api/mpm-plugins');

        $response->assertStatus(200);
        $this->assertSame(
            array_column(MpmPlugin::cases(), 'value'),
            array_column($response->json('data'), 'slug'),
        );
        $response->assertJsonPath('data.0.display_name', MpmPlugin::SparkMeter->displayName());
    }
}
