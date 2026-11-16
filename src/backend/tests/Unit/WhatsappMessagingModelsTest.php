<?php

namespace Tests\Unit;

use App\Models\MpmPlugin;
use App\Models\Person\Person;
use App\Models\Sms;
use App\Models\SmsAttempt;
use App\Plugins\WhatsappMessaging\Models\WhatsappConsentLog;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappMessagingModelsTest extends TestCase {
    use RefreshMultipleDatabases;

    public function testContactRelationsPersistAndResolve(): void {
        $person = Person::factory()->create();
        $sms = new Sms([
            'receiver' => '+255700000000',
            'body' => 'Your token is 1234',
            'status' => Sms::STATUS_STORED,
            'sender_id' => 1,
        ]);
        $sms->trigger()->associate($person);
        $sms->save();

        $contact = new WhatsappContact([
            'phone' => '+255700000000',
            'wa_id' => '255700000000',
            'opt_in_status' => WhatsappContact::OPT_IN_STATUS_OPTED_IN,
            'opted_in_at' => now(),
        ]);
        $contact->person()->associate($person);
        $contact->save();

        $consentLog = $contact->consentLogs()->create([
            'action' => WhatsappConsentLog::ACTION_OPT_IN,
            'source' => WhatsappConsentLog::SOURCE_KEYWORD,
            'raw_message' => 'START',
        ]);

        $attempt = new SmsAttempt([
            'gateway_id' => MpmPlugin::WHATSAPP_MESSAGING,
            'message_type' => SmsAttempt::MESSAGE_TYPE_TEXT,
            'status' => SmsAttempt::STATUS_ACCEPTED,
            'external_id' => 'wamid.HBgLMjU1NzAwMDAwMDAwFQIAERgSQ0E',
        ]);
        $attempt->sms()->associate($sms);
        $attempt->save();

        $contact->refresh();
        $this->assertTrue($contact->person?->is($person));
        $this->assertTrue($contact->consentLogs->first()?->is($consentLog));
        $this->assertTrue($consentLog->contact?->is($contact));
        $this->assertTrue($attempt->sms?->is($sms));
        $this->assertTrue($sms->deliveryAttempts->first()?->is($attempt));
    }

    public function testCredentialAppliesColumnDefaults(): void {
        $credential = WhatsappCredential::query()->create([
            'access_token' => 'encrypted-access-token',
            'app_secret' => 'encrypted-app-secret',
            'webhook_verify_token' => 'encrypted-webhook-verify-token',
        ])->refresh();

        $this->assertSame('v26.0', $credential->graph_api_version);
    }
}
