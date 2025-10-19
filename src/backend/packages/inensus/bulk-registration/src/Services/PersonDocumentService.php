<?php

namespace Inensus\BulkRegistration\Services;

use App\Models\PersonDocument;

class PersonDocumentService extends CreatorService {
    public function __construct(PersonDocument $personDocument) {
        parent::__construct($personDocument);
    }

    /**
     * @param list<array<string, mixed>> $personDocuments
     */
    public function createRelatedDataIfDoesNotExists(array $personDocuments): void {
        foreach ($personDocuments as $personDocument) {
            PersonDocument::query()->firstOrCreate($personDocument, $personDocument);
        }
    }

    /**
     * @param array<string, mixed> $csvData
     */
    public function resolveCsvDataFromComingRow(array $csvData): void {
        $personDocsConfig = config('bulk-registration.csv_fields.person_docs');
        $personDocuments = [];

        foreach ($personDocsConfig as $docConfig) {
            $personDocumentData = [
                'person_id' => $csvData[$docConfig['person_id']],
                'name' => $csvData[$docConfig['type']],
                'type' => $docConfig['type'],
                'location' => $docConfig['location'],
            ];
            $personDocuments[] = $personDocumentData;
        }
        $this->createRelatedDataIfDoesNotExists($personDocuments);
    }
}
