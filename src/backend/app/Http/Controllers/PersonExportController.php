<?php

namespace App\Http\Controllers;

use App\People\Export\PersonExportService;
use App\Services\PersonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PersonExportController extends Controller {
    public function __construct(
        private PersonService $personService,
        private PersonExportService $peopleExportService,
    ) {}

    public function download(Request $request): BinaryFileResponse {
        $format = $request->get('format', 'excel');

        if ($format === 'csv') {
            return $this->downloadCsv($request);
        }

        return $this->downloadExcel($request);
    }

    public function downloadExcel(Request $request): BinaryFileResponse {
        $miniGrid = $request->get('miniGrid');
        $village = $request->get('village');
        $deviceType = $request->get('deviceType');
        $isActive = $request->get('isActive');
        $isActive = $isActive === 'true' ? true : ($isActive === 'false' ? false : null);

        $people = $this->personService->getAllForExport($miniGrid, $village, $deviceType, $isActive);
        $this->peopleExportService->createSpreadSheetFromTemplate($this->peopleExportService->getTemplatePath());
        $this->peopleExportService->setPeopleData($people);
        $this->peopleExportService->setExportingData();
        $this->peopleExportService->writePeopleData();
        $pathToSpreadSheet = $this->peopleExportService->saveSpreadSheet();

        $path = Storage::disk('local')->path($pathToSpreadSheet);

        return response()->download($path, 'customer_export_'.now()->format('Ymd_His').'.xlsx');
    }

    public function downloadCsv(Request $request): BinaryFileResponse {
        $miniGrid = $request->get('miniGrid');
        $village = $request->get('village');
        $deviceType = $request->get('deviceType');
        $isActive = $request->get('isActive');

        $isActive = $isActive === 'true' ? true : ($isActive === 'false' ? false : null);

        $people = $this->personService->getAllForExport($miniGrid, $village, $deviceType, $isActive);

        $this->peopleExportService->setPeopleData($people);
        $this->peopleExportService->setExportingData();
        $headers = ['Title', 'Name', 'Surname', 'Birth Date', 'Sex', 'Email', 'Phone', 'City', 'Device Serial', 'Agent Name'];
        $csvPath = $this->peopleExportService->saveCsv($headers);

        $path = Storage::disk('local')->path($csvPath);

        return response()->download($path, 'customer_export_'.now()->format('Ymd_His').'.csv');
    }
}
