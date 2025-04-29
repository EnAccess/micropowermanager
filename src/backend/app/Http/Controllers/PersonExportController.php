<?php

namespace App\Http\Controllers;

use App\People\Export\PersonExportService;
use App\Services\PersonService;
use Illuminate\Http\Request;
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
        $people = $this->personService->getAllForExport();
        $this->peopleExportService->createSpreadSheetFromTemplate($this->peopleExportService->getTemplatePath());
        $this->peopleExportService->setPeopleData($people);
        $this->peopleExportService->setExportingData();
        $this->peopleExportService->writePeopleData();
        $path = $this->peopleExportService->saveSpreadSheet();

        return response()->download($path, 'customer_export_'.now()->format('Ymd_His').'.xlsx');
    }

    public function downloadCsv(Request $request): BinaryFileResponse {
        $people = $this->personService->getAllForExport();

        $this->peopleExportService->setPeopleData($people);
        $this->peopleExportService->setExportingData();
        $headers = ['Title', 'Name', 'Surname', 'Birth Date', 'Sex', 'Email', 'Phone', 'City', 'Device Serial', 'Agent Name'];
        $csvPath = $this->peopleExportService->saveCsv($headers);

        return response()->download($csvPath, 'customer_export_'.now()->format('Ymd_His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
