<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Plugins\CalinMeter\Models\CalinCredential;
use Database\Factories\UserFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class CalinMeterCredentialTest extends TestCase {
    private const array CREDENTIALS = [
        'api_url' => 'http://api.calinhost.com/api',
        'user_id' => 'user-1',
        'api_key' => 'secret',
    ];

    public function testSavingCredentialsWorksWhenTheCredentialRowWasDeleted(): void {
        CalinCredential::query()->delete();

        $response = $this->actingAs(UserFactory::new()->create())
            ->putJson('/api/calin-meters/calin-credential', self::CREDENTIALS);

        $response->assertOk();
        $response->assertJsonPath('data.user_id', 'user-1');
        $response->assertJsonPath('data.api_url', 'http://api.calinhost.com/api');
        $this->assertSame(1, CalinCredential::query()->count());
    }

    public function testCheckConnectionReturnsValidWhenCalinAcceptsTheLogin(): void {
        $this->fakeCalin(new Response(200, [], json_encode(['result_code' => 0, 'reason' => 'OK', 'result' => '1234'])));

        $response = $this->actingAs(UserFactory::new()->create())
            ->postJson('/api/calin-meters/calin-credential/check', self::CREDENTIALS);

        $response->assertOk();
        $response->assertJsonPath('valid', true);
    }

    public function testCheckConnectionWithWrongCredentialsReturnsCalinsReason(): void {
        $passwordError = json_encode(['result_code' => 4, 'reason' => 'Password error', 'result' => null]);
        $this->fakeCalin(new Response(200, [], $passwordError), new Response(200, [], $passwordError));

        $response = $this->actingAs(UserFactory::new()->create())
            ->postJson('/api/calin-meters/calin-credential/check', self::CREDENTIALS);

        $response->assertStatus(502);
        $response->assertJsonPath('message', 'Calin rejected the request: Password error (code 4)');
    }

    private function fakeCalin(Response ...$responses): void {
        $this->app->instance(Client::class, new Client(['handler' => HandlerStack::create(new MockHandler($responses))]));
    }
}
