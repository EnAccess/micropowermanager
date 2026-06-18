<?php

namespace Tests\Feature;

use App\Models\PersonDocument;
use App\Services\PersonDocumentUploadService;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\CreateEnvironments;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class PersonDocumentTest extends TestCase {
    use RefreshMultipleDatabases;
    use CreateEnvironments;

    public function testItUploadsAPdfDocumentForACustomer(): void {
        Storage::fake();
        $user = UserFactory::new()->create();
        $this->user = $user;
        $this->assignRole($user, 'admin');
        $person = PersonFactory::new()->create();

        $response = $this->actingAs($user)->post(
            sprintf('/api/people/%s/documents', $person->id),
            [
                'file' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
                'type' => 'contract',
                'additional_json' => ['signed_at' => '2026-05-20'],
            ]
        );

        $response->assertStatus(201);
        $this->assertEquals(1, $person->uploadedDocuments()->count());
        $document = $person->uploadedDocuments()->first();
        $this->assertSame(PersonDocument::CATEGORY_CUSTOMER_UPLOAD, $document->category);
        $this->assertSame('contract', $document->type);
        $this->assertSame('contract.pdf', $document->original_name);
        $this->assertSame(['signed_at' => '2026-05-20'], $document->additional_json);
        Storage::assertExists($document->location.'/'.$document->name);
    }

    public function testItUploadsADocxDocumentForACustomer(): void {
        Storage::fake();
        $user = UserFactory::new()->create();
        $this->user = $user;
        $this->assignRole($user, 'admin');
        $person = PersonFactory::new()->create();

        $response = $this->actingAs($user)->post(
            sprintf('/api/people/%s/documents', $person->id),
            [
                'file' => UploadedFile::fake()->create(
                    'questionnaire.docx',
                    50,
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ),
                'type' => 'questionnaire',
            ]
        );

        $response->assertStatus(201);
        $this->assertEquals(1, $person->uploadedDocuments()->count());
    }

    public function testItRejectsUnsupportedFileType(): void {
        Storage::fake();
        $user = UserFactory::new()->create();
        $this->user = $user;
        $this->assignRole($user, 'admin');
        $person = PersonFactory::new()->create();

        $response = $this->actingAs($user)->post(
            sprintf('/api/people/%s/documents', $person->id),
            [
                'file' => UploadedFile::fake()->image('photo.jpg'),
                'type' => 'photo',
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    }

    public function testItRejectsFilesOverFiveMegabytes(): void {
        Storage::fake();
        $user = UserFactory::new()->create();
        $this->user = $user;
        $this->assignRole($user, 'admin');
        $person = PersonFactory::new()->create();

        $response = $this->actingAs($user)->post(
            sprintf('/api/people/%s/documents', $person->id),
            [
                'file' => UploadedFile::fake()->create('huge.pdf', 5121, 'application/pdf'),
                'type' => 'contract',
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    }

    public function testItRejectsAFourthDocument(): void {
        Storage::fake();
        $user = UserFactory::new()->create();
        $this->user = $user;
        $this->assignRole($user, 'admin');
        $person = PersonFactory::new()->create();

        for ($i = 0; $i < PersonDocumentUploadService::MAX_DOCUMENTS_PER_PERSON; ++$i) {
            $this->actingAs($user)->post(
                sprintf('/api/people/%s/documents', $person->id),
                [
                    'file' => UploadedFile::fake()->create("doc{$i}.pdf", 50, 'application/pdf'),
                    'type' => 'contract',
                ]
            )->assertStatus(201);
        }

        $response = $this->actingAs($user)->post(
            sprintf('/api/people/%s/documents', $person->id),
            [
                'file' => UploadedFile::fake()->create('overflow.pdf', 50, 'application/pdf'),
                'type' => 'contract',
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    }

    public function testItUpdatesAdditionalJsonForADocument(): void {
        Storage::fake();
        $user = UserFactory::new()->create();
        $this->user = $user;
        $this->assignRole($user, 'admin');
        $person = PersonFactory::new()->create();

        $this->actingAs($user)->post(
            sprintf('/api/people/%s/documents', $person->id),
            [
                'file' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
                'type' => 'contract',
                'additional_json' => ['signed_at' => '2026-05-20'],
            ]
        );
        $document = $person->uploadedDocuments()->first();

        $response = $this->actingAs($user)->patch(
            '/api/person-documents/'.$document->id,
            ['additional_json' => ['signed_at' => '2026-06-01', 'witness' => 'Ada']]
        );

        $response->assertStatus(200);
        $this->assertEquals(
            ['signed_at' => '2026-06-01', 'witness' => 'Ada'],
            $document->fresh()->additional_json
        );
    }

    public function testItClearsAdditionalJsonWhenSentEmpty(): void {
        Storage::fake();
        $user = UserFactory::new()->create();
        $this->user = $user;
        $this->assignRole($user, 'admin');
        $person = PersonFactory::new()->create();

        $this->actingAs($user)->post(
            sprintf('/api/people/%s/documents', $person->id),
            [
                'file' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
                'type' => 'contract',
                'additional_json' => ['signed_at' => '2026-05-20'],
            ]
        );
        $document = $person->uploadedDocuments()->first();

        $response = $this->actingAs($user)->patch(
            '/api/person-documents/'.$document->id,
            ['additional_json' => []]
        );

        $response->assertStatus(200);
        $this->assertNull($document->fresh()->additional_json);
    }

    public function testItRejectsAnUpdateWithoutAdditionalJson(): void {
        Storage::fake();
        $user = UserFactory::new()->create();
        $this->user = $user;
        $this->assignRole($user, 'admin');
        $person = PersonFactory::new()->create();

        $this->actingAs($user)->post(
            sprintf('/api/people/%s/documents', $person->id),
            [
                'file' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
                'type' => 'contract',
            ]
        );
        $document = $person->uploadedDocuments()->first();

        $response = $this->actingAs($user)->patchJson(
            '/api/person-documents/'.$document->id,
            []
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('additional_json');
    }

    public function testItDeletesADocumentAndItsFile(): void {
        Storage::fake();
        $user = UserFactory::new()->create();
        $this->user = $user;
        $this->assignRole($user, 'admin');
        $person = PersonFactory::new()->create();

        $this->actingAs($user)->post(
            sprintf('/api/people/%s/documents', $person->id),
            [
                'file' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
                'type' => 'contract',
            ]
        );

        $document = $person->uploadedDocuments()->first();
        $storedPath = $document->location.'/'.$document->name;
        Storage::assertExists($storedPath);

        $response = $this->actingAs($user)->delete('/api/person-documents/'.$document->id);
        $response->assertStatus(200);

        Storage::assertMissing($storedPath);
        $this->assertEquals(0, $person->uploadedDocuments()->count());
    }

    public function testItDownloadsAStoredDocument(): void {
        Storage::fake();
        $user = UserFactory::new()->create();
        $this->user = $user;
        $this->assignRole($user, 'admin');
        $person = PersonFactory::new()->create();

        $this->actingAs($user)->post(
            sprintf('/api/people/%s/documents', $person->id),
            [
                'file' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
                'type' => 'contract',
            ]
        );

        $document = $person->uploadedDocuments()->first();

        $response = $this->actingAs($user)->get('/api/person-documents/'.$document->id.'/download');

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function testIdentityDocumentRelationStillResolvesForProspectPlugin(): void {
        $person = PersonFactory::new()->create();
        PersonDocument::query()->create([
            'person_id' => $person->id,
            'category' => PersonDocument::CATEGORY_IDENTITY_RECORD,
            'type' => 'passport',
            'name' => 'AB123456',
        ]);
        PersonDocument::query()->create([
            'person_id' => $person->id,
            'category' => PersonDocument::CATEGORY_CUSTOMER_UPLOAD,
            'type' => 'contract',
            'name' => 'stored.pdf',
            'original_name' => 'contract.pdf',
        ]);

        $person->refresh();

        $this->assertNotNull($person->identityDocument);
        $this->assertSame('passport', $person->identityDocument->type);
        $this->assertSame(2, $person->personDocuments()->count());
        $this->assertSame(1, $person->uploadedDocuments()->count());
    }
}
