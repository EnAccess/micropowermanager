<?php

namespace Tests\Feature;

use App\Models\Person\Person;
use App\Models\User;
use App\Plugins\WhatsappMessaging\Models\WhatsappConsentLog;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use Database\Factories\Address\AddressFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappContactControllerTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string PHONE = '+255712345678';

    private User $user;

    protected function setUp(): void {
        parent::setUp();
        $this->user = UserFactory::new()->create();
    }

    public function testOptInWithoutConsentNoteReturns422(): void {
        $person = $this->createCustomerWithPrimaryPhone();

        $response = $this->actingAs($this->user)
            ->postJson(sprintf('/api/whatsapp-messaging/contacts/%d/opt-in', $person->id));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('consent_note');
        $this->assertSame(0, WhatsappContact::query()->count());
    }

    public function testOptInAlwaysUsesPrimaryPhoneAndLogsOperator(): void {
        $person = $this->createCustomerWithPrimaryPhone();

        $response = $this->actingAs($this->user)
            ->postJson(sprintf('/api/whatsapp-messaging/contacts/%d/opt-in', $person->id), [
                'phone' => '+255787654321',
                'consent_note' => 'Agreed on the registration form',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.phone', self::PHONE);
        $response->assertJsonPath('data.person_id', $person->id);
        $response->assertJsonPath('data.opt_in_status', WhatsappContact::OPT_IN_STATUS_OPTED_IN);
        $response->assertJsonCount(1, 'data.consent_logs');

        $log = WhatsappConsentLog::query()->sole();
        $this->assertSame(WhatsappConsentLog::SOURCE_OPERATOR, $log->source);
        $this->assertSame($this->user->id, $log->user_id);
        $this->assertSame('Agreed on the registration form', $log->raw_message);
    }

    public function testOptInWithoutPrimaryPhoneReturns422(): void {
        $person = PersonFactory::new()->create();

        $response = $this->actingAs($this->user)
            ->postJson(sprintf('/api/whatsapp-messaging/contacts/%d/opt-in', $person->id), [
                'consent_note' => 'Agreed in person',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The customer has no primary phone number. Add a phone number to an address in the customer\'s Address section and mark it as Primary.');
        $this->assertSame(0, WhatsappContact::query()->count());
    }

    public function testOptOutAfterOptInReturnsNewestConsentLogFirst(): void {
        $person = $this->createCustomerWithPrimaryPhone();
        $this->actingAs($this->user)
            ->postJson(sprintf('/api/whatsapp-messaging/contacts/%d/opt-in', $person->id), [
                'consent_note' => 'Agreed in person',
            ]);

        $response = $this->actingAs($this->user)
            ->postJson(sprintf('/api/whatsapp-messaging/contacts/%d/opt-out', $person->id));

        $response->assertStatus(200);
        $response->assertJsonPath('data.opt_in_status', WhatsappContact::OPT_IN_STATUS_OPTED_OUT);
        $response->assertJsonCount(2, 'data.consent_logs');
        $response->assertJsonPath('data.consent_logs.0.action', WhatsappConsentLog::ACTION_OPT_OUT);
        $response->assertJsonPath('data.consent_logs.0.user.name', $this->user->name);
    }

    public function testShowReturnsNullWhenCustomerHasNoContact(): void {
        $person = $this->createCustomerWithPrimaryPhone();

        $response = $this->actingAs($this->user)
            ->getJson(sprintf('/api/whatsapp-messaging/contacts/%d', $person->id));

        $response->assertStatus(200);
        $response->assertExactJson(['data' => null]);
        $this->assertSame(0, WhatsappContact::query()->count());
    }

    public function testShowReturnsStatusAndConsentHistoryOfPrimaryPhone(): void {
        $person = $this->createCustomerWithPrimaryPhone();
        $this->actingAs($this->user)
            ->postJson(sprintf('/api/whatsapp-messaging/contacts/%d/opt-in', $person->id), [
                'consent_note' => 'Agreed in person',
            ]);

        $response = $this->actingAs($this->user)
            ->getJson(sprintf('/api/whatsapp-messaging/contacts/%d', $person->id));

        $response->assertStatus(200);
        $response->assertJsonPath('data.phone', self::PHONE);
        $response->assertJsonPath('data.opt_in_status', WhatsappContact::OPT_IN_STATUS_OPTED_IN);
        $response->assertJsonPath('data.consent_logs.0.raw_message', 'Agreed in person');
    }

    private function createCustomerWithPrimaryPhone(): Person {
        $person = PersonFactory::new()->create();
        $address = AddressFactory::new()->make(['phone' => self::PHONE, 'is_primary' => 1]);
        $address->owner()->associate($person);
        $address->save();

        return $person;
    }
}
