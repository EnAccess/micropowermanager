<?php

namespace Tests\Feature;

use App\Models\Address\Address;
use App\Models\Person\Person;
use App\Models\PersonDocument;
use App\Services\PersonDocumentUploadService;
use Database\Factories\CityFactory;
use Database\Factories\MiniGridFactory;
use Database\Factories\Person\PersonFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentAppDocumentTest extends TestCase {
    use CreateEnvironments;

    public function testAgentUploadsAPdfDocumentForOwnCustomer(): void {
        Storage::fake();
        $this->bootstrapAgentWithCustomer();
        $customer = Person::query()->where('is_customer', 1)->first();

        $response = $this->actingAs($this->agent)->post(
            sprintf('/api/app/agents/customers/%d/documents', $customer->id),
            [
                'file' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
                'type' => 'contract',
                'additional_json' => ['signed_at' => '2026-05-21'],
            ],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(201);
        $document = $customer->uploadedDocuments()->first();
        $this->assertNotNull($document);
        $this->assertSame(PersonDocument::CATEGORY_CUSTOMER_UPLOAD, $document->category);
        $this->assertSame('contract.pdf', $document->original_name);
        Storage::assertExists($document->location.'/'.$document->name);
    }

    public function testAgentListsDocumentsForOwnCustomer(): void {
        Storage::fake();
        $this->bootstrapAgentWithCustomer();
        $customer = Person::query()->where('is_customer', 1)->first();

        $this->actingAs($this->agent)->post(
            sprintf('/api/app/agents/customers/%d/documents', $customer->id),
            [
                'file' => UploadedFile::fake()->create('contract.pdf', 50, 'application/pdf'),
                'type' => 'contract',
            ],
            ['Accept' => 'application/json']
        );

        $response = $this->actingAs($this->agent)
            ->getJson(sprintf('/api/app/agents/customers/%d/documents', $customer->id));

        $response->assertStatus(200);
        $this->assertCount(1, $response['data']);
    }

    public function testAgentCannotUploadForCustomerOutsideTheirMiniGrid(): void {
        Storage::fake();
        $this->bootstrapAgentWithCustomer();
        $foreignCustomer = $this->createCustomerInForeignMiniGrid();

        $response = $this->actingAs($this->agent)->post(
            sprintf('/api/app/agents/customers/%d/documents', $foreignCustomer->id),
            [
                'file' => UploadedFile::fake()->create('contract.pdf', 50, 'application/pdf'),
                'type' => 'contract',
            ],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(404);
    }

    public function testAgentDownloadsTheirCustomerDocument(): void {
        Storage::fake();
        $this->bootstrapAgentWithCustomer();
        $customer = Person::query()->where('is_customer', 1)->first();

        $this->actingAs($this->agent)->post(
            sprintf('/api/app/agents/customers/%d/documents', $customer->id),
            [
                'file' => UploadedFile::fake()->create('contract.pdf', 50, 'application/pdf'),
                'type' => 'contract',
            ],
            ['Accept' => 'application/json']
        );
        $document = $customer->uploadedDocuments()->first();

        $response = $this->actingAs($this->agent)
            ->getJson('/api/app/agents/customers/documents/'.$document->id.'/download');

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function testAgentUpdatesAdditionalJsonForOwnCustomerDocument(): void {
        Storage::fake();
        $this->bootstrapAgentWithCustomer();
        $customer = Person::query()->where('is_customer', 1)->first();

        $this->actingAs($this->agent)->post(
            sprintf('/api/app/agents/customers/%d/documents', $customer->id),
            [
                'file' => UploadedFile::fake()->create('contract.pdf', 50, 'application/pdf'),
                'type' => 'contract',
                'additional_json' => ['signed_at' => '2026-05-21'],
            ],
            ['Accept' => 'application/json']
        );
        $document = $customer->uploadedDocuments()->first();

        $response = $this->actingAs($this->agent)->patchJson(
            '/api/app/agents/customers/documents/'.$document->id,
            ['additional_json' => ['signed_at' => '2026-06-02', 'witness' => 'Ada']]
        );

        $response->assertStatus(200);
        $this->assertEquals(
            ['signed_at' => '2026-06-02', 'witness' => 'Ada'],
            $document->fresh()->additional_json
        );
    }

    public function testAgentCannotUpdateDocumentOfForeignCustomer(): void {
        Storage::fake();
        $this->bootstrapAgentWithCustomer();
        $foreignCustomer = $this->createCustomerInForeignMiniGrid();

        $foreignDocument = PersonDocument::query()->create([
            'person_id' => $foreignCustomer->id,
            'category' => PersonDocument::CATEGORY_CUSTOMER_UPLOAD,
            'type' => 'contract',
            'name' => 'stored.pdf',
            'original_name' => 'contract.pdf',
            'location' => 'documents/companies/1/persons/'.$foreignCustomer->id,
        ]);

        $response = $this->actingAs($this->agent)->patchJson(
            '/api/app/agents/customers/documents/'.$foreignDocument->id,
            ['additional_json' => ['tampered' => 'yes']]
        );

        $response->assertStatus(403);
        $this->assertNull($foreignDocument->fresh()->additional_json);
    }

    public function testAgentCannotDownloadDocumentOfForeignCustomer(): void {
        Storage::fake();
        $this->bootstrapAgentWithCustomer();
        $foreignCustomer = $this->createCustomerInForeignMiniGrid();

        $foreignDocument = PersonDocument::query()->create([
            'person_id' => $foreignCustomer->id,
            'category' => PersonDocument::CATEGORY_CUSTOMER_UPLOAD,
            'type' => 'contract',
            'name' => 'stored.pdf',
            'original_name' => 'contract.pdf',
            'location' => 'documents/companies/1/persons/'.$foreignCustomer->id,
        ]);

        $response = $this->actingAs($this->agent)
            ->getJson('/api/app/agents/customers/documents/'.$foreignDocument->id.'/download');

        $response->assertStatus(403);
    }

    public function testAgentDeletesTheirCustomerDocument(): void {
        Storage::fake();
        $this->bootstrapAgentWithCustomer();
        $customer = Person::query()->where('is_customer', 1)->first();

        $this->actingAs($this->agent)->post(
            sprintf('/api/app/agents/customers/%d/documents', $customer->id),
            [
                'file' => UploadedFile::fake()->create('contract.pdf', 50, 'application/pdf'),
                'type' => 'contract',
            ],
            ['Accept' => 'application/json']
        );
        $document = $customer->uploadedDocuments()->first();
        $storedPath = $document->location.'/'.$document->name;
        Storage::assertExists($storedPath);

        $response = $this->actingAs($this->agent)
            ->deleteJson('/api/app/agents/customers/documents/'.$document->id);

        $response->assertStatus(200);
        Storage::assertMissing($storedPath);
        $this->assertEquals(0, $customer->uploadedDocuments()->count());
    }

    public function testAgentCannotExceedMaxThreeDocuments(): void {
        Storage::fake();
        $this->bootstrapAgentWithCustomer();
        $customer = Person::query()->where('is_customer', 1)->first();

        for ($i = 0; $i < PersonDocumentUploadService::MAX_DOCUMENTS_PER_PERSON; ++$i) {
            $this->actingAs($this->agent)->post(
                sprintf('/api/app/agents/customers/%d/documents', $customer->id),
                [
                    'file' => UploadedFile::fake()->create("doc{$i}.pdf", 50, 'application/pdf'),
                    'type' => 'contract',
                ],
                ['Accept' => 'application/json']
            )->assertStatus(201);
        }

        $response = $this->actingAs($this->agent)->post(
            sprintf('/api/app/agents/customers/%d/documents', $customer->id),
            [
                'file' => UploadedFile::fake()->create('overflow.pdf', 50, 'application/pdf'),
                'type' => 'contract',
            ],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    }

    private function bootstrapAgentWithCustomer(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createMeterType();
        $this->createMeterTariff();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createPerson(1);
    }

    private function createCustomerInForeignMiniGrid(): Person {
        $foreignMiniGrid = MiniGridFactory::new()->create([
            'cluster_id' => $this->cluster->id,
        ]);
        $foreignCity = CityFactory::new()->create([
            'country_id' => 1,
            'mini_grid_id' => $foreignMiniGrid->id,
        ]);
        $foreignPerson = PersonFactory::new()->create(['is_customer' => 1]);
        $address = Address::query()->make([
            'email' => $this->faker->email(),
            'phone' => $this->faker->e164PhoneNumber(),
            'street' => '',
            'city_id' => $foreignCity->id,
            'is_primary' => 1,
        ]);
        $address->owner()->associate($foreignPerson);
        $address->save();

        return $foreignPerson;
    }
}
