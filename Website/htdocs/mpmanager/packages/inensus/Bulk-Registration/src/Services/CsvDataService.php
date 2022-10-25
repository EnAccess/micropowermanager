<?php


namespace Inensus\BulkRegistration\Services;


use Inensus\BulkRegistration\Helpers\CsvDataProcessor;
use Inensus\BulkRegistration\Helpers\CsvFileParser;
use Inensus\BulkRegistration\Models\CsvData;

class CsvDataService
{
    private $csvData;
    private $csvDataProcessor;
    private $csvFileParser;

    public function __construct(CsvData $csvData, CsvFileParser $csvFileParser,CsvDataProcessor $csvDataProcessor)
    {
        $this->csvData = $csvData;
        $this->csvDataProcessor=$csvDataProcessor;
        $this->csvFileParser=$csvFileParser;

    }

    public function create($request)
    {

        $path = $request->file('csv');
        $parsedCsvData = $this->csvFileParser->parseCsvFromFilePath($path);
        $recentlyCreatedRecords = $this->csvDataProcessor->processParsedCsvData($parsedCsvData);
        $csvData = $this->csvData->newQuery()->create([
            'csv_filename' => $request->file('csv')->getClientOriginalName(),
            'user_id' => auth()->user()->id,
            'csv_data' => json_encode(array_values($parsedCsvData), true)
        ]);
         $csvData['recently_created_records']=$recentlyCreatedRecords;
        return $csvData;
    }
}