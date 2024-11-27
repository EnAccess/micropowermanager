<?php

namespace Inensus\BulkRegistration\Test\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inensus\BulkRegistration\Models\CsvData;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ImportCsv extends TestCase {
    use RefreshDatabase;

    /** @test */
    public function isUserSentCsv() {
        $this->withoutExceptionHandling();
        $user = factory(User::class)->create();

        $uploadedFile = new UploadedFile(__DIR__.'/test-files/test.csv', 'test.csv', null, null, true);
        $csv_data_file = [
            'csv' => $uploadedFile,
        ];
        $response = $this->actingAs($user)->post('/api/bulk-register/import-csv', $csv_data_file);
        $createdCsvData = CsvData::query()->first();
        $jsonResponse = json_decode($response->getContent(), true);
        $response->assertStatus(201);
        $response->assertJson([
            'data' => [
                'type' => 'csv_data',
                'csv_data_id' => $createdCsvData->id,
                'attributes' => [
                    'created_person_id' => $user->id,
                    'csv_filename' => 'test.csv',
                    'recently_created_records' => $jsonResponse['data']['attributes']['recently_created_records'],
                ],
            ],
        ]);
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
