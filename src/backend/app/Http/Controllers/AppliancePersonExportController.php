<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppliancePersonExportRequest;
use App\Services\AppliancePersonService;
use App\Services\ExportServices\AbstractExportService;
use App\Services\ExportServices\AppliancePersonExportService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('Export', 'Export data as Excel, CSV, or JSON.', weight: 11)]
class AppliancePersonExportController extends Controller {
    public function __construct(
        private AppliancePersonService $appliancePersonService,
        private AppliancePersonExportService $appliancePersonExportService,
    ) {}

    /**
     * Export AppliancePerson records.
     *
     * Downloads AppliancePerson records (an appliance sold to a customer) as an Excel or CSV file, or returns them as JSON.
     *
     * The exported columns round-trip straight back into the `import/appliance-people` endpoint:
     * customers are identified by name and surname, appliances by name — so an AppliancePerson can only be
     * re-imported after its customer and appliance already exist in the target instance.
     * Monetary fields are exported as raw numbers (no currency symbol or thousands separator).
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function download(AppliancePersonExportRequest $request): StreamedResponse|JsonResponse {
        $format = $request->string('format', 'excel')->toString();

        if ($format === 'csv') {
            return $this->downloadCsv($request);
        }

        if ($format === 'json') {
            return $this->downloadJson($request);
        }

        return $this->downloadExcel($request);
    }

    private function downloadExcel(AppliancePersonExportRequest $request): StreamedResponse {
        $appliancePeople = $this->appliancePersonService->getAllForExport(...$this->filters($request));

        $this->appliancePersonExportService->createSpreadSheetFromTemplate($this->appliancePersonExportService->getTemplatePath());
        $this->appliancePersonExportService->setAppliancePeopleData($appliancePeople);
        $this->appliancePersonExportService->setExportingData();
        $this->appliancePersonExportService->writeData();
        $pathToSpreadSheet = $this->appliancePersonExportService->saveSpreadSheet();

        return Storage::download($pathToSpreadSheet, 'appliance_person_export_'.now()->format('Ymd_His').'.xlsx', ['Content-Type' => AbstractExportService::XLSX_CONTENT_TYPE]);
    }

    private function downloadCsv(AppliancePersonExportRequest $request): StreamedResponse {
        $appliancePeople = $this->appliancePersonService->getAllForExport(...$this->filters($request));

        $this->appliancePersonExportService->setAppliancePeopleData($appliancePeople);
        $this->appliancePersonExportService->setExportingData();
        $csvPath = $this->appliancePersonExportService->saveCsv(AppliancePersonExportService::HEADERS);

        return Storage::download($csvPath, 'appliance_person_export_'.now()->format('Ymd_His').'.csv', ['Content-Type' => AbstractExportService::CSV_CONTENT_TYPE]);
    }

    private function downloadJson(AppliancePersonExportRequest $request): JsonResponse {
        [$paymentType, $personId] = $this->filters($request);

        $appliancePeople = $this->appliancePersonService->getAllForExport($paymentType, $personId);
        $this->appliancePersonExportService->setAppliancePeopleData($appliancePeople);
        $jsonData = $this->appliancePersonExportService->exportDataToArray();

        return response()->json([
            'data' => $jsonData,
            'meta' => [
                'total' => count($jsonData),
                'filters' => [
                    'payment_type' => $paymentType,
                    'person_id' => $personId,
                ],
                'exported_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * @return array{0: ?string, 1: ?int}
     */
    private function filters(AppliancePersonExportRequest $request): array {
        return [
            $request->filled('paymentType') ? $request->string('paymentType')->toString() : null,
            $request->filled('personId') ? $request->integer('personId') : null,
        ];
    }
}
