<?php

namespace Tests\Feature;

use App\Jobs\OperatorDashboardRebuildJob;
use App\Services\OperatorDashboardService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class OperatorDashboardEndpointTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string USERNAME = 'operator';
    private const string PASSWORD = 'operator-secret';

    protected function setUp(): void {
        parent::setUp();

        config()->set('micropowermanager.operator_dashboard.basic_auth.username', self::USERNAME);
        config()->set('micropowermanager.operator_dashboard.basic_auth.password', self::PASSWORD);

        Cache::flush();
    }

    /**
     * The operator endpoints carry no tenant JWT, so they must be excluded from the
     * global tenant-resolving middleware. Without that exclusion this returns 500.
     */
    public function testItIsReachableWithoutATenantToken(): void {
        $response = $this->getJson('/api/operator/dashboard', $this->authorization());

        $response->assertStatus(200);
    }

    public function testItRejectsAMissingCredential(): void {
        $response = $this->getJson('/api/operator/dashboard');

        $response->assertStatus(401);
    }

    public function testItRejectsAWrongCredential(): void {
        $response = $this->getJson('/api/operator/dashboard', [
            'Authorization' => 'Basic '.base64_encode(self::USERNAME.':wrong'),
        ]);

        $response->assertStatus(401);
    }

    /**
     * A WWW-Authenticate header makes the browser open its own credential dialog on
     * an XHR 401, which would pre-empt the dashboard's own login form.
     */
    public function testItDoesNotChallengeWithABasicRealm(): void {
        $response = $this->getJson('/api/operator/dashboard');

        $response->assertStatus(401);
        $this->assertFalse($response->headers->has('WWW-Authenticate'));
    }

    public function testItRefusesWhenNoCredentialIsConfigured(): void {
        config()->set('micropowermanager.operator_dashboard.basic_auth.username');
        config()->set('micropowermanager.operator_dashboard.basic_auth.password');

        $response = $this->getJson('/api/operator/dashboard', $this->authorization());

        $response->assertStatus(403);
    }

    public function testItReturnsAnEmptyDocumentOnAColdCache(): void {
        $response = $this->getJson('/api/operator/dashboard', $this->authorization());

        $response->assertStatus(200);
        $this->assertNull($response['data']['generated_at']);
        $this->assertTrue($response['data']['stale']);
        $this->assertSame([], $response['data']['tenants']);
        $this->assertSame(0, $response['data']['summary']['tenants_total']);
    }

    public function testItNeverExposesAPlatformWideMoneyTotal(): void {
        resolve(OperatorDashboardService::class)->rebuild();

        $response = $this->getJson('/api/operator/dashboard', $this->authorization());

        $response->assertStatus(200);
        $summaryKeys = array_keys($response['data']['summary']);
        foreach ($summaryKeys as $key) {
            $this->assertStringNotContainsString('volume', $key);
            $this->assertStringNotContainsString('revenue', $key);
        }
    }

    public function testItReturnsATenantDetail(): void {
        resolve(OperatorDashboardService::class)->rebuild();

        $response = $this->getJson('/api/operator/dashboard/tenants/'.$this->companyId, $this->authorization());

        $response->assertStatus(200);
        $this->assertSame($this->companyId, $response['data']['id']);
        $this->assertArrayHasKey('currency', $response['data']);
        $this->assertArrayHasKey('plugins', $response['data']);
    }

    public function testItReturnsNotFoundForAnUnknownTenant(): void {
        $response = $this->getJson('/api/operator/dashboard/tenants/987654', $this->authorization());

        $response->assertStatus(404);
        $response->assertJsonStructure(['message']);
    }

    public function testItQueuesARebuildOnlyOnceWhileOneIsInFlight(): void {
        Queue::fake();

        $first = $this->postJson('/api/operator/dashboard/refresh', [], $this->authorization());
        $second = $this->postJson('/api/operator/dashboard/refresh', [], $this->authorization());

        $first->assertStatus(202);
        $second->assertStatus(202);
        $this->assertTrue($first['data']['refreshing']);
        Queue::assertPushed(OperatorDashboardRebuildJob::class, 1);
    }

    /** @return array<string, string> */
    private function authorization(): array {
        return ['Authorization' => 'Basic '.base64_encode(self::USERNAME.':'.self::PASSWORD)];
    }
}
