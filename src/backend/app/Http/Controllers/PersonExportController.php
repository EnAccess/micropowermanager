<?php

namespace App\Http\Controllers;

use App\Services\ExportServices\PersonExportService;
use App\Services\PersonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonExportController extends Controller {
    public function __construct(
        private PersonService $personService,
        private PersonExportService $peopleExportService,
    ) {}

    public function download(Request $request): StreamedResponse|JsonResponse {
        $format = $request->get('format', 'excel');

        if ($format === 'csv') {
            return $this->downloadCsv($request);
        }

        if ($format === 'json') {
            return $this->downloadJson($request);
        }

        return $this->downloadExcel($request);
    }

    public function downloadExcel(Request $request): StreamedResponse {
        $miniGridName = $request->get('miniGrid');
        $villageName = $request->get('village');
        $deviceType = $request->get('deviceType');
        $isActive = $request->get('isActive');
        $isActive = $isActive === 'true' ? true : ($isActive === 'false' ? false : null);

        $people = $this->personService->getAllForExport($miniGridName, $villageName, $deviceType, $isActive);
        $this->peopleExportService->createSpreadSheetFromTemplate($this->peopleExportService->getTemplatePath());
        $this->peopleExportService->setPeopleData($people);
        $this->peopleExportService->setExportingData();
        $this->peopleExportService->writePeopleData();
        $pathToSpreadSheet = $this->peopleExportService->saveSpreadSheet();

        return Storage::download($pathToSpreadSheet, 'customer_export_'.now()->format('Ymd_His').'.xlsx');
    }

    public function downloadCsv(Request $request): StreamedResponse {
        $miniGridName = $request->get('miniGrid');
        $villageName = $request->get('village');
        $deviceType = $request->get('deviceType');
        $isActive = $request->get('isActive');

        $isActive = $isActive === 'true' ? true : ($isActive === 'false' ? false : null);

        $people = $this->personService->getAllForExport($miniGridName, $villageName, $deviceType, $isActive);

        $this->peopleExportService->setPeopleData($people);
        $this->peopleExportService->setExportingData();
        $headers = ['Title', 'Name', 'Surname', 'Birth Date', 'Gender', 'Email', 'Phone', 'City', 'Device Serial', 'Agent Name'];
        $csvPath = $this->peopleExportService->saveCsv($headers);

        return Storage::download($csvPath, 'customer_export_'.now()->format('Ymd_His').'.csv');
    }

    public function downloadJson(Request $request): JsonResponse {
        $miniGridName = $request->get('miniGrid');
        $villageName = $request->get('village');
        $deviceType = $request->get('deviceType');
        $isActive = $request->get('isActive');

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
