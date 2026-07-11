<?php

namespace App\Http\Controllers;

use App\Http\Requests\PersonExportRequest;
use App\Services\ExportServices\AbstractExportService;
use App\Services\ExportServices\PersonExportService;
use App\Services\PersonService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('Export', 'Export data as Excel, CSV, or JSON.', weight: 11)]
class PersonExportController extends Controller {
    public function __construct(
        private PersonService $personService,
        private PersonExportService $peopleExportService,
    ) {}

    /**
     * Export customers.
     *
     * Downloads customer records as an Excel or CSV file, or returns them as JSON.
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function download(PersonExportRequest $request): StreamedResponse|JsonResponse {
        $format = $request->input('format', 'excel');

        if ($format === 'csv') {
            return $this->downloadCsv($request);
        }

        if ($format === 'json') {
            return $this->downloadJson($request);
        }

        return $this->downloadExcel($request);
    }

    public function downloadExcel(PersonExportRequest $request): StreamedResponse {
        $miniGridName = $request->input('miniGrid');
        $villageName = $request->input('village');
        $deviceType = $request->input('deviceType');
        $isActive = $request->input('isActive');
        $isActive = $isActive === 'true' ? true : ($isActive === 'false' ? false : null);

        $people = $this->personService->getAllForExport($miniGridName, $villageName, $deviceType, $isActive);
        $this->peopleExportService->createSpreadSheetFromTemplate($this->peopleExportService->getTemplatePath());
        $this->peopleExportService->setPeopleData($people);
        $this->peopleExportService->setExportingData();
        $this->peopleExportService->writePeopleData();
        $pathToSpreadSheet = $this->peopleExportService->saveSpreadSheet();

        return Storage::download($pathToSpreadSheet, 'customer_export_'.now()->format('Ymd_His').'.xlsx', ['Content-Type' => AbstractExportService::XLSX_CONTENT_TYPE]);
    }

    public function downloadCsv(PersonExportRequest $request): StreamedResponse {
        $miniGridName = $request->input('miniGrid');
        $villageName = $request->input('village');
        $deviceType = $request->input('deviceType');
        $isActive = $request->input('isActive');

        $isActive = $isActive === 'true' ? true : ($isActive === 'false' ? false : null);

        $people = $this->personService->getAllForExport($miniGridName, $villageName, $deviceType, $isActive);

        $this->peopleExportService->setPeopleData($people);
        $this->peopleExportService->setExportingData();
        $csvPath = $this->peopleExportService->saveCsv(PersonExportService::HEADERS);

        return Storage::download($csvPath, 'customer_export_'.now()->format('Ymd_His').'.csv', ['Content-Type' => AbstractExportService::CSV_CONTENT_TYPE]);
    }

    public function downloadJson(PersonExportRequest $request): JsonResponse {
        $miniGridName = $request->input('miniGrid');
        $villageName = $request->input('village');
        $deviceType = $request->input('deviceType');
        $isActive = $request->input('isActive');

        $isActive = $isActive === 'true' ? true : ($isActive === 'false' ? false : null);

        $people = $this->personService->getAllForExport($miniGridName, $villageName, $deviceType, $isActive);

        $this->peopleExportService->setPeopleData($people);
        $jsonData = $this->peopleExportService->exportDataToArray();

        return response()->json([
            'data' => $jsonData,
            'meta' => [
                'total' => count($jsonData),
                'filters' => [
                    'mini_grid' => $miniGridName,
                    'village' => $villageName,
                    'device_type' => $deviceType,
                    'is_active' => $isActive,
                ],
                'exported_at' => now()->toISOString(),
            ],
        ]);
    }
}
