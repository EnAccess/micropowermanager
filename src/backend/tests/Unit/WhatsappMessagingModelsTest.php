<?php

namespace Tests\Unit;

use App\Models\Person\Person;
use App\Models\Sms;
use App\Plugins\WhatsappMessaging\Models\WhatsappConsentLog;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use App\Plugins\WhatsappMessaging\Models\WhatsappTemplate;
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

        $message = new WhatsappMessage([
            'message_type' => WhatsappMessage::MESSAGE_TYPE_TEXT,
            'status' => WhatsappMessage::STATUS_ACCEPTED,
            'wamid' => 'wamid.HBgLMjU1NzAwMDAwMDAwFQIAERgSQ0E',
        ]);
        $message->sms()->associate($sms);
        $message->contact()->associate($contact);
        $message->save();

        $contact->refresh();
        $this->assertTrue($contact->person?->is($person));
        $this->assertTrue($contact->consentLogs->first()?->is($consentLog));
        $this->assertTrue($contact->messages->first()?->is($message));
        $this->assertTrue($consentLog->contact?->is($contact));
        $this->assertTrue($message->sms?->is($sms));
        $this->assertTrue($message->contact?->is($contact));
    }

    public function testCredentialAppliesColumnDefaults(): void {
        $credential = WhatsappCredential::query()->create([
            'access_token' => 'encrypted-access-token',
            'app_secret' => 'encrypted-app-secret',
            'webhook_verify_token' => 'encrypted-webhook-verify-token',
        ])->refresh();

        $this->assertSame('v26.0', $credential->graph_api_version);
        $this->assertTrue($credential->sms_fallback_enabled);
    }

    public function testTemplateCastsParametersToAnOrderedList(): void {
        $template = WhatsappTemplate::query()->create([
            'body_parser' => 'TokenConfirmationMeter',
            'template_name' => 'token_confirmation_meter',
            'parameters' => ['name', 'surname', 'token'],
        ])->refresh();

        $this->assertSame(['name', 'surname', 'token'], $template->parameters);
        $this->assertSame('en', $template->language_code);
        $this->assertTrue($template->is_active);
    }
}
