<?php

namespace App\Plugins\SparkMeter\Tests\Feature;

use App\Models\MiniGrid;
use App\Models\User;
use App\Plugins\SparkMeter\Models\SmCredential;
use App\Plugins\SparkMeter\Models\SmSite;
use Database\Factories\ClusterFactory;
use Database\Factories\UserFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class CredentialSettingsTest extends TestCase {
    private User $user;

    /** @var array<int, array<string, mixed>> */
    private array $history = [];

    protected function setUp(): void {
        parent::setUp();
        $this->user = UserFactory::new()->create();
    }

    /**
     * @param array<int, Response> $responses
     */
    private function fakeSparkMeter(array $responses): void {
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($this->history));
        $this->app->instance(Client::class, new Client(['handler' => $handler]));
    }

    private function createCredential(): SmCredential {
        return SmCredential::query()->create([
            'api_url' => 'https://cloud.test/api/v0',
            'api_key' => Crypt::encryptString('stored-key'),
            'api_secret' => Crypt::encryptString('stored-secret'),
            'is_authenticated' => 1,
        ]);
    }

    private function createSite(): SmSite {
        $cluster = ClusterFactory::new()->create(['manager_id' => $this->user->id]);
        $miniGrid = MiniGrid::query()->create(['name' => 'Ground', 'cluster_id' => $cluster->id]);

        return SmSite::query()->create([
            'site_id' => 'site-1',
            'mpm_mini_grid_id' => $miniGrid->id,
            'thundercloud_url' => 'https://ground.test/api/v0',
            'thundercloud_token' => 'stored-token',
            'is_authenticated' => 1,
            'is_online' => 1,
        ]);
    }

    public function testShowCredentialReturnsDecryptedSecrets(): void {
        $this->createCredential();

        $response = $this->actingAs($this->user)->getJson('/api/spark-meters/sm-credential');

        $response->assertOk()
            ->assertJsonPath('data.api_url', 'https://cloud.test/api/v0')
            ->assertJsonPath('data.api_key', 'stored-key')
            ->assertJsonPath('data.api_secret', 'stored-secret');
    }

    public function testUpdateCredentialKeepsStoredSecretsWhenOmitted(): void {
        $credential = $this->createCredential();
        $this->fakeSparkMeter([
            new Response(200, [], (string) json_encode([
                'error' => null,
                'organizations' => [['id' => 'org-1', 'code' => 'ORG', 'display_name' => 'Org', 'name' => 'Org']],
            ])),
        ]);

        $response = $this->actingAs($this->user)->putJson('/api/spark-meters/sm-credential', [
            'id' => $credential->id,
            'api_url' => 'https://other-cloud.test/api/v0',
        ]);

        $response->assertOk();
        $credential->refresh();
        $this->assertSame('https://other-cloud.test/api/v0', $credential->api_url);
        $this->assertSame('stored-key', Crypt::decryptString($credential->api_key));
        $this->assertSame('stored-secret', Crypt::decryptString($credential->api_secret));
        $this->assertSame('stored-key', $this->history[0]['request']->getHeaderLine('X-API-KEY'));
    }

    public function testUpdateCredentialRequiresSecretsWhenNoneAreStored(): void {
        $credential = SmCredential::query()->create(['api_url' => 'https://cloud.test/api/v0', 'is_authenticated' => 0]);

        $response = $this->actingAs($this->user)->putJson('/api/spark-meters/sm-credential', [
            'id' => $credential->id,
            'api_url' => 'https://cloud.test/api/v0',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['api_key', 'api_secret']);
    }

    public function testSiteListMakesNoApiCalls(): void {
        $this->createSite();
        $this->fakeSparkMeter([]);

        $response = $this->actingAs($this->user)->getJson('/api/spark-meters/sm-site');

        $response->assertOk()->assertJsonPath('data.0.thundercloud_token', 'stored-token');
        $this->assertCount(0, $this->history);
    }

    public function testUpdateSiteKeepsStoredTokenWhenOmitted(): void {
        $site = $this->createSite();
        $this->fakeSparkMeter([
            new Response(200, [], (string) json_encode([
                'error' => null,
                'grids' => [['last_sync_date' => now()->toIso8601String()]],
            ])),
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/spark-meters/sm-site/{$site->id}", [
            'thundercloud_url' => 'https://ground-two.test/api/v0',
        ]);

        $response->assertOk()->assertJsonPath('data.is_authenticated', 1);
        $site->refresh();
        $this->assertSame('https://ground-two.test/api/v0', $site->thundercloud_url);
        $this->assertSame('stored-token', $site->thundercloud_token);
        $this->assertSame('stored-token', $this->history[0]['request']->getHeaderLine('Authentication-Token'));
    }

    public function testCheckCredentialsTestsTheSubmittedValuesWithoutSavingThem(): void {
        $credential = $this->createCredential();
        $this->fakeSparkMeter([
            new Response(200, [], (string) json_encode(['error' => null, 'organizations' => []])),
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/spark-meters/sm-credential/check', [
            'api_url' => 'https://other-cloud.test/api/v0',
            'api_key' => 'typed-key',
        ]);

        $response->assertOk()->assertJson(['valid' => true]);
        $request = $this->history[0]['request'];
        $this->assertSame('https://other-cloud.test/api/v0/organizations', (string) $request->getUri());
        $this->assertSame('typed-key', $request->getHeaderLine('X-API-KEY'));
        $this->assertSame('stored-secret', $request->getHeaderLine('X-API-SECRET'));
        $credential->refresh();
        $this->assertSame('https://cloud.test/api/v0', $credential->api_url);
        $this->assertSame('stored-key', Crypt::decryptString($credential->api_key));
    }

    public function testCheckCredentialsReportsRejectedCredentials(): void {
        $this->createCredential();
        $this->fakeSparkMeter([new Response(401, [], '{"error": "unauthorized"}')]);

        $response = $this->actingAs($this->user)->postJson('/api/spark-meters/sm-credential/check', [
            'api_url' => 'https://cloud.test/api/v0',
        ]);

        $response->assertOk()->assertJson(['valid' => false]);
        $this->assertNotEmpty($response->json('message'));
    }

    public function testUpdateCredentialMarksUnreachableApiAsNotAuthenticated(): void {
        $credential = $this->createCredential();
        $this->fakeSparkMeter([new Response(404, [], '{"error": "not found"}')]);

        $response = $this->actingAs($this->user)->putJson('/api/spark-meters/sm-credential', [
            'id' => $credential->id,
            'api_url' => 'https://cloud.test/api/v1',
        ]);

        $response->assertOk()->assertJsonPath('data.is_authenticated', false);
        $this->assertSame('https://cloud.test/api/v1', $credential->refresh()->api_url);
    }
}
