<?php

namespace Tests\Feature;

use App\Providers\TelescopeServiceProvider;
use Illuminate\Http\Request;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Tests\WhatsAppTestCase;

class WhatsAppTelescopePrivacyTest extends WhatsAppTestCase {
    public function testSensitiveRequestsCannotEnterTelescopeQueueButUnrelatedRequestsCan(): void {
        config(['telescope.enable_error_only_filter' => false]);
        new TelescopeServiceProvider($this->app)->register();
        $queue = new \ReflectionProperty(Telescope::class, 'entriesQueue');
        $original = $queue->getValue();
        $originalRequest = request();
        Telescope::startRecording(false);
        try {
            $queue->setValue(null, []);
            foreach (['/api/whatsapp/credential', '/api/whatsapp/webhook/1'] as $path) {
                $this->app->instance('request', Request::create($path));
                Telescope::recordRequest(new IncomingEntry(['uri' => $path, 'payload' => ['secret' => 'test-only-secret']]));
                Telescope::recordQuery(new IncomingEntry(['sql' => 'test-only-query']));
                $this->assertCount(0, $queue->getValue());
            }
            $this->app->instance('request', Request::create('/api/unrelated'));
            Telescope::recordRequest(new IncomingEntry(['uri' => '/api/unrelated']));
            $this->assertCount(1, $queue->getValue());
        } finally {
            $queue->setValue(null, $original);
            Telescope::stopRecording();
            $this->app->instance('request', $originalRequest);
        }
    }
}
