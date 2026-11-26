<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Lib\IMpmPluginProvider;
use App\Services\MpmPluginCatalog;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MpmPluginCatalogTest extends TestCase {
    public function testEveryPluginDescribesItselfCompletely(): void {
        $catalog = resolve(MpmPluginCatalog::class);
        $commands = array_keys(Artisan::all());

        $this->assertCount(33, $catalog->all());
        foreach ($catalog->all() as $slug => $plugin) {
            $this->assertMatchesRegularExpression('/^[a-z0-9]+(_[a-z0-9]+)*$/', $slug);
            $this->assertNotSame('', $plugin->displayName());
            $this->assertNotSame('', $plugin->description());
            $this->assertContains($plugin->usageType(), ['mini-grid', 'shs', 'e-bike', 'general']);
            $this->assertContains($catalog->installationCommand($slug), $commands, "{$slug} has no installation command.");
        }
    }

    public function testEveryPluginHasADistinctSlug(): void {
        $slugs = array_map(
            fn (IMpmPluginProvider $provider): string => $provider->slug(),
            array_values($this->app->getProviders(IMpmPluginProvider::class)),
        );

        $this->assertSame($slugs, array_values(array_unique($slugs)));
    }

    public function testItListsTheCatalog(): void {
        $catalog = resolve(MpmPluginCatalog::class);

        $response = $this->getJson('/api/mpm-plugins');

        $response->assertStatus(200);
        $this->assertSame($catalog->slugs(), array_column($response->json('data'), 'slug'));
        $this->assertSame(
            array_map(fn (IMpmPluginProvider $plugin): string => $plugin->displayName(), array_values($catalog->all())),
            array_column($response->json('data'), 'display_name'),
        );
    }
}
