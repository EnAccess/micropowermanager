<?php

namespace Tests\Feature;

use App\Models\Sms;
use App\Models\User;
use Database\Factories\Address\AddressFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class SmsConversationSearchTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string CUSTOMER_PHONE = '+255712345678';
    private const string STRANGER_PHONE = '+255799887766';

    private User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = UserFactory::new()->create(['company_id' => $this->companyId]);

        $this->registerCustomer('Ivah', "O'Hara", self::CUSTOMER_PHONE);
        $this->storeOutgoingSms(self::CUSTOMER_PHONE);
        $this->storeOutgoingSms(self::STRANGER_PHONE);
    }

    public function testUnfilteredListReturnsEveryConversation(): void {
        $this->assertEqualsCanonicalizing(
            [self::CUSTOMER_PHONE, self::STRANGER_PHONE],
            $this->searchReceivers(null)
        );
    }

    public function testConversationsAreOrderedByMostRecentMessage(): void {
        Sms::query()->where('receiver', self::CUSTOMER_PHONE)
            ->update(['created_at' => '2026-01-01 10:00:00']);
        Sms::query()->where('receiver', self::STRANGER_PHONE)
            ->update(['created_at' => '2026-01-02 10:00:00']);

        $this->assertSame(
            [self::STRANGER_PHONE, self::CUSTOMER_PHONE],
            $this->searchReceivers(null)
        );
    }

    public function testSearchMatchesPartOfThePhoneNumber(): void {
        $this->assertSame([self::CUSTOMER_PHONE], $this->searchReceivers('7123456'));
    }

    public function testSearchMatchesTheTrailingDigitsOfThePhoneNumber(): void {
        $this->assertSame([self::CUSTOMER_PHONE], $this->searchReceivers('345678'));
    }

    public function testSearchMatchesTheCustomerName(): void {
        $this->assertSame([self::CUSTOMER_PHONE], $this->searchReceivers('iva'));
    }

    public function testSearchMatchesTheCustomerSurname(): void {
        $this->assertSame([self::CUSTOMER_PHONE], $this->searchReceivers('hara'));
    }

    public function testBlankSearchTermIsToleratedAndReturnsEveryConversation(): void {
        $this->assertEqualsCanonicalizing(
            [self::CUSTOMER_PHONE, self::STRANGER_PHONE],
            $this->searchReceivers('   ')
        );
    }

    public function testSearchWithoutMatchesReturnsNothing(): void {
        $this->assertSame([], $this->searchReceivers('nobody-by-that-name'));
    }

    /**
     * @return array<int, string>
     */
    private function searchReceivers(?string $term): array {
        $url = '/api/sms'.($term === null ? '' : '?term='.urlencode($term));

        $response = $this->actingAs($this->user)->get($url);
        $response->assertOk();

        return collect($response['data'])->pluck('receiver')->all();
    }

    private function registerCustomer(string $name, string $surname, string $phone): void {
        $person = PersonFactory::new()->create(['name' => $name, 'surname' => $surname]);
        $address = AddressFactory::new()->make(['phone' => $phone]);
        $address->owner()->associate($person);
        $address->save();

        // The customer match joins on `sms.receiver` = `addresses.phone`, so the
        // number has to land in the column in the same E.164 form.
        $this->assertDatabaseHas('addresses', ['phone' => $phone], 'tenant');
    }

    private function storeOutgoingSms(string $receiver): void {
        Sms::query()->create([
            'receiver' => $receiver,
            'body' => 'Your token is 1234',
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_SENT,
        ]);
    }
}
