<?php

declare(strict_types=1);

namespace App\Plugins\CalinMeter\Tests\Unit;

use App\Plugins\CalinMeter\Models\CalinCredential;
use App\Plugins\CalinMeter\Services\CalinCredentialService;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class CalinCredentialServiceTest extends TestCase {
    use RefreshMultipleDatabases;

    public function testUpdateCredentialsCreatesTheRowWhenItIsMissing(): void {
        CalinCredential::query()->delete();

        $credential = $this->app->make(CalinCredentialService::class)->updateCredentials([
            'api_url' => 'http://api.calinhost.com/api',
            'user_id' => 'user-1',
            'api_key' => 'secret',
        ]);

        $this->assertSame(1, CalinCredential::query()->count());
        $this->assertSame('user-1', $credential->user_id);
        $this->assertSame('secret', $credential->api_key);

        $stored = CalinCredential::query()->firstOrFail();
        $this->assertNotSame('user-1', $stored->getRawOriginal('user_id'));
        $this->assertNotSame('secret', $stored->getRawOriginal('api_key'));
        $this->assertSame('http://api.calinhost.com/api', $stored->getRawOriginal('api_url'));
    }

    public function testUpdateCredentialsUpdatesTheExistingRow(): void {
        $service = $this->app->make(CalinCredentialService::class);
        $service->createCredentials();

        $service->updateCredentials(['api_url' => 'http://api.calinhost.com/api', 'user_id' => 'user-2', 'api_key' => 'secret-2']);

        $this->assertSame(1, CalinCredential::query()->count());
        $this->assertSame('user-2', $service->getCredentials()?->user_id);
    }
}
