<?php

namespace App\Plugins\BulkRegistration\Http\Controllers;

use App\Plugins\BulkRegistration\Http\Requests\ImportCsvRequest;
use App\Plugins\BulkRegistration\Http\Resources\CsvData as CsvDataResource;
use App\Plugins\BulkRegistration\Services\CsvDataService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Routing\Controller;

#[Group('Plugins / Bulk Registration', 'API endpoints for registering customers in bulk via CSV import')]
class ImportCsvController extends Controller {
    public function __construct(private CsvDataService $csvDataService) {
        set_time_limit(8000000);
    }

    public function store(ImportCsvRequest $request): CsvDataResource {
        return new CsvDataResource($this->csvDataService->create($request));
    }
}
