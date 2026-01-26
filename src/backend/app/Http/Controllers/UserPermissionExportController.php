<?php

namespace App\Http\Controllers;

use App\Services\ExportServices\UserPermissionExportService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserPermissionExportController extends Controller {
    public function __construct(
        private UserService $userService,
        private UserPermissionExportService $userPermissionExportService,
    ) {}

    public function download(Request $request): BinaryFileResponse|JsonResponse {
        $format = $request->get('format', 'json');

        if ($format === 'excel') {
            return $this->downloadExcel($request);
        }

        if ($format === 'csv') {
            return $this->downloadCsv($request);
        }

        return $this->downloadJson($request);
    }

    public function downloadExcel(Request $request): BinaryFileResponse {
        $users = $this->userService->getUsersWithRolesAndPermissions();
        $this->userPermissionExportService->createSpreadSheetFromTemplate($this->userPermissionExportService->getTemplatePath());
        $this->userPermissionExportService->setUserData($users);
        $this->userPermissionExportService->setExportingData();
        $this->userPermissionExportService->writeUserPermissionData();
        $pathToSpreadSheet = $this->userPermissionExportService->saveSpreadSheet();

        $path = Storage::path($pathToSpreadSheet);

        return response()->download($path, 'user_permission_export_'.now()->format('Ymd_His').'.xlsx');
    }

    public function downloadCsv(Request $request): BinaryFileResponse {
        $users = $this->userService->getUsersWithRolesAndPermissions();

        $this->userPermissionExportService->setUserData($users);
        $this->userPermissionExportService->setExportingData();
        $headers = ['Name', 'Email', 'Roles', 'Permissions', 'Created At'];
        $csvPath = $this->userPermissionExportService->saveCsv($headers);

        $path = Storage::path($csvPath);

        return response()->download($path, 'user_permission_export_'.now()->format('Ymd_His').'.csv');
    }

    public function downloadJson(Request $request): JsonResponse {
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
