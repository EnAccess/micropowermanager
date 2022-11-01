<?php

namespace Inensus\BulkRegistration\Http\Controllers;


use Illuminate\Routing\Controller;
use Inensus\BulkRegistration\Exceptions\ClusterNotFoundException;
use Inensus\BulkRegistration\Exceptions\CsvDataParserException;
use Inensus\BulkRegistration\Exceptions\ManufacturerNotSupportedException;
use Inensus\BulkRegistration\Http\Requests\ImportCsvRequest;
use Inensus\BulkRegistration\Http\Resources\CsvData as CsvDataResource;
use Inensus\BulkRegistration\Services\CsvDataService;

class ImportCsvController extends Controller
{
    private $csvDataService;

    public function __construct(CsvDataService $csvDataService)
    {
        $this->csvDataService = $csvDataService;
    }

    public function store(ImportCsvRequest $request)
    {
        return new CsvDataResource($this->csvDataService->create($request));
    }

}