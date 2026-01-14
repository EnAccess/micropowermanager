<?php

namespace App\Plugins\BulkRegistration\Test\Feature;

use App\Models\User;
use App\Plugins\BulkRegistration\Models\CsvData;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportCsv extends TestCase {
    use RefreshDatabase;
    use InteractsWithAuthentication;

    /** @test */
    public function isUserSentCsv(): void {
        $this->withoutExceptionHandling();
        $user = User::factory()->createOne();

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
}
