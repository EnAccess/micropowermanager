<?php

namespace Tests\Feature;

use App\Models\Address\Address;
use App\Models\Device;
use Database\Factories\Person\PersonFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class PersonSearchPhoneTest extends TestCase {
    use CreateEnvironments;

    public function testSearchTermWithEncodedPlusFindsPersonByPhone(): void {
        $this->createTestData();
        $phoneOwner = $this->createPersonWithPhone('+49123456');
        $serialOwner = $this->createPersonWithDeviceSerial('49123456');

        $response = $this->actingAs($this->user)->getJson('/api/people/search?term=%2B49');

        $response->assertStatus(200);
        $foundIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($phoneOwner->id, $foundIds, 'Encoded +49 should match phone +49123456');
        $this->assertNotContains($serialOwner->id, $foundIds, 'Encoded +49 should not match serial 49123456');
    }

    public function testSearchTermWithoutPlusFindsPersonByPhone(): void {
        $this->createTestData();
        $phoneOwner = $this->createPersonWithPhone('+49123456');
        $serialOwner = $this->createPersonWithDeviceSerial('49123456');

        $response = $this->actingAs($this->user)->getJson('/api/people/search?term=49');

        $response->assertStatus(200);
        $foundIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($phoneOwner->id, $foundIds, 'Bare 49 should match phone +49123456 despite the stored leading +');
        $this->assertContains($serialOwner->id, $foundIds, 'Bare 49 should match serial 49123456');
    }

    public function testSearchTermWithRawPlusIsDecodedAsSpaceAndStillFindsPhone(): void {
        $this->createTestData();
        $phoneOwner = $this->createPersonWithPhone('+49123456');
        $serialOwner = $this->createPersonWithDeviceSerial('49123456');

        $response = $this->actingAs($this->user)->getJson('/api/people/search?term=+49');

        $response->assertStatus(200);
        $foundIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($phoneOwner->id, $foundIds, 'Raw +49 decodes to " 49" and trims to "49", which still matches the phone');
        $this->assertContains($serialOwner->id, $foundIds, 'Raw +49 decodes to " 49" and trims to "49", matching the serial');
    }

    private function createPersonWithPhone(string $phone) {
        $person = PersonFactory::new()->create(['is_customer' => 1]);
        $address = Address::query()->make([
            'phone' => $phone,
            'is_primary' => 1,
        ]);
        $address->owner()->associate($person)->save();

        return $person;
    }

    private function createPersonWithDeviceSerial(string $serial) {
        $person = PersonFactory::new()->create(['is_customer' => 1]);
        Device::query()->create([
            'person_id' => $person->id,
            'device_id' => 1,
            'device_type' => 'meter',
            'device_serial' => $serial,
        ]);

        return $person;
    }
}
