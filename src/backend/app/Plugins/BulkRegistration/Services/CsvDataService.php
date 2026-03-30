<?php

namespace App\Plugins\BulkRegistration\Services;

use App\Plugins\BulkRegistration\Exceptions\MissingDataException;
use App\Plugins\BulkRegistration\Helpers\CsvDataProcessor;
use App\Plugins\BulkRegistration\Helpers\CsvFileParser;
use App\Plugins\BulkRegistration\Models\CsvData;
use Illuminate\Http\Request;

class CsvDataService {
    public function __construct(
        private CsvData $csvData,
        private CsvFileParser $csvFileParser,
        private CsvDataProcessor $csvDataProcessor,
    ) {}

    public function create(Request $request): CsvData {
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
