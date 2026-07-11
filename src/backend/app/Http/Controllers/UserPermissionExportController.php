<?php

namespace App\Http\Controllers;

use App\Services\ExportServices\AbstractExportService;
use App\Services\ExportServices\UserPermissionExportService;
use App\Services\UserService;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('Export', 'Export data as Excel, CSV, or JSON.', weight: 11)]
class UserPermissionExportController extends Controller {
    public function __construct(
        private UserService $userService,
        private UserPermissionExportService $userPermissionExportService,
    ) {}

    /**
     * Export users and permissions.
     *
     * Returns users with their roles and permissions as JSON, or downloads them as an Excel or CSV file.
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    #[QueryParameter('format', description: 'Export format.', type: "'json'|'excel'|'csv'", default: 'json')]
    public function download(Request $request): StreamedResponse|JsonResponse {
        $format = $request->input('format', 'json');

        if ($format === 'excel') {
            return $this->downloadExcel();
        }

        if ($format === 'csv') {
            return $this->downloadCsv();
        }

        return $this->downloadJson();
    }

    public function downloadExcel(): StreamedResponse {
        $users = $this->userService->getUsersWithRolesAndPermissions();
        $this->userPermissionExportService->createSpreadSheetFromTemplate($this->userPermissionExportService->getTemplatePath());
        $this->userPermissionExportService->setUserData($users);
        $this->userPermissionExportService->setExportingData();
        $this->userPermissionExportService->writeUserPermissionData();
        $pathToSpreadSheet = $this->userPermissionExportService->saveSpreadSheet();

        return Storage::download($pathToSpreadSheet, 'user_permission_export_'.now()->format('Ymd_His').'.xlsx', ['Content-Type' => AbstractExportService::XLSX_CONTENT_TYPE]);
    }

    public function downloadCsv(): StreamedResponse {
        $users = $this->userService->getUsersWithRolesAndPermissions();

        $this->userPermissionExportService->setUserData($users);
        $this->userPermissionExportService->setExportingData();
        $headers = ['Name', 'Email', 'Roles', 'Permissions', 'Created At'];
        $csvPath = $this->userPermissionExportService->saveCsv($headers);

        return Storage::download($csvPath, 'user_permission_export_'.now()->format('Ymd_His').'.csv', ['Content-Type' => AbstractExportService::CSV_CONTENT_TYPE]);
    }

    public function downloadJson(): JsonResponse {
        $users = $this->userService->getUsersWithRolesAndPermissions();

        $this->userPermissionExportService->setUserData($users);
        $jsonData = $this->userPermissionExportService->exportDataToArray();

        return response()->json([
            'data' => $jsonData,
            'meta' => [
                'total' => count($jsonData),
                'exported_at' => now()->toISOString(),
            ],
        ]);
    }
}
