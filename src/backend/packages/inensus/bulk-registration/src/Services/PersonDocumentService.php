<?php

namespace Inensus\BulkRegistration\Services;

use App\Models\PersonDocument;

class PersonDocumentService extends CreatorService {
    public function __construct(PersonDocument $personDocument) {
        parent::__construct($personDocument);
    }

    public function createRelatedDataIfDoesNotExists($personDocuments) {
        foreach ($personDocuments as $personDocument) {
            PersonDocument::query()->firstOrCreate($personDocument, $personDocument);
        }
    }

    public function resolveCsvDataFromComingRow($csvData) {
        $personDocsConfig = config('bulk-registration.csv_fields.person_docs');
        $personDocuments = [];

        foreach ($personDocsConfig as $docConfig) {
            $personDocumentData = [
                'person_id' => $csvData[$docConfig['person_id']],
                'name' => $csvData[$docConfig['type']],
                'type' => $docConfig['type'],
                'location' => $docConfig['location'],
            ];
            array_push($personDocuments, $personDocumentData);
        }
        $this->createRelatedDataIfDoesNotExists($personDocuments);
    }
}
