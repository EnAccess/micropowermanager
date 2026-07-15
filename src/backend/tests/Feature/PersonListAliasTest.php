<?php

namespace Tests\Feature;

use Database\Factories\Person\PersonFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class PersonListAliasTest extends TestCase {
    use CreateEnvironments;

    public function testDeprecatedListAliasesReturnTheSamePeopleAsTheCanonicalEndpoint(): void {
        $this->createTestData();
        PersonFactory::new()->count(3)->create(['is_customer' => 1]);

        $canonical = $this->actingAs($this->user)->getJson('/api/people');
        $legacyAlias = $this->actingAs($this->user)->getJson('/api/people/all');
        $registrationAppAlias = $this->actingAs($this->user)->getJson('/api/customer-registration-app/people');

        $canonical->assertStatus(200);
        $legacyAlias->assertStatus(200);
        $registrationAppAlias->assertStatus(200);

        $canonicalIds = collect($canonical->json('data'))->pluck('id')->all();
        $this->assertCount(3, $canonicalIds);
        $this->assertSame($canonicalIds, collect($legacyAlias->json('data'))->pluck('id')->all());
        $this->assertSame($canonicalIds, collect($registrationAppAlias->json('data'))->pluck('id')->all());
    }
}
