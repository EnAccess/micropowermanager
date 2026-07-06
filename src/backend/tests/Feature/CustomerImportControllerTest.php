<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Person\Person;
use Database\Factories\UserFactory;
use Tests\TestCase;

class CustomerImportControllerTest extends TestCase {
    public function testImportCreatesACustomerWithAllOptionalFields(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'customers');

        $response = $this->actingAs($user)->postJson('/api/import/customers', [
            'data' => [[
                'name' => 'Ada',
                'surname' => 'Lovelace',
                'title' => 'Dr.',
                'gender' => 'female',
                'email' => 'ada@example.org',
                'phone' => '+255700000001',
                'street' => 'Analytical Engine Rd 1',
            ]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.success', true);
        $response->assertJsonPath('data.added_count', 1);

        $person = Person::query()->where('name', 'Ada')->where('surname', 'Lovelace')->first();
        $this->assertNotNull($person);
        $this->assertSame('Dr.', $person->title);

        $address = $person->addresses()->where('is_primary', 1)->first();
        $this->assertNotNull($address);
        $this->assertSame('Analytical Engine Rd 1', $address->street);
        $this->assertSame('+255700000001', (string) $address->phone);
    }

    public function testImportCreatesACustomerFromNameOnly(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'customers');

        $response = $this->actingAs($user)->postJson('/api/import/customers', [
            'data' => [['name' => 'Grace']],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.success', true);
        $response->assertJsonPath('data.added_count', 1);

        $person = Person::query()->where('name', 'Grace')->where('surname', '')->first();
        $this->assertNotNull($person);
    }

    public function testImportRejectsAnUnknownCity(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'customers');

        $response = $this->actingAs($user)->postJson('/api/import/customers', [
            'data' => [['name' => 'Alan', 'city' => 'City That Does Not Exist']],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['data.0.city']);
    }
}
