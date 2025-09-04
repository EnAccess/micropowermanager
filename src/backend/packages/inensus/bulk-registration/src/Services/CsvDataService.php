<?php

namespace Inensus\BulkRegistration\Services;

use Inensus\BulkRegistration\Exceptions\MissingDataException;
use Inensus\BulkRegistration\Helpers\CsvDataProcessor;
use Inensus\BulkRegistration\Helpers\CsvFileParser;
use Inensus\BulkRegistration\Models\CsvData;

class CsvDataService {
    public function __construct(
        private CsvData $csvData,
        private CsvFileParser $csvFileParser,
        private CsvDataProcessor $csvDataProcessor,
    ) {}

    public function create($request) {
        $path = $request->file('csv');
        $parsedCsvData = $this->csvFileParser->parseCsvFromFilePath($path);
        $message = '';
        try {
            $recentlyCreatedRecords = $this->csvDataProcessor->processParsedCsvData($parsedCsvData);
        } catch (MissingDataException $e) {
            $recentlyCreatedRecords = [];
            $message = $e->getMessage();
        }

        $csvData = $this->csvData->newQuery()->create([
            'csv_filename' => $request->file('csv')->getClientOriginalName(),
            'user_id' => auth('api')->user()->id,
            'csv_data' => json_encode(array_values($parsedCsvData), 1),
        ]);
        $csvData['recently_created_records'] = $recentlyCreatedRecords;
        $csvData['alert'] = $message;

        return $csvData;
    }
}
