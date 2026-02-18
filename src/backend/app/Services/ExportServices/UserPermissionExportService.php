<?php

namespace App\Services\ExportServices;

use App\Models\User;
use Illuminate\Support\Collection;

class UserPermissionExportService extends AbstractExportService {
    /** @var Collection<int, User> */
    private Collection $userData;

    public function writeUserPermissionData(): void {
        $this->setActivatedSheet('Sheet1');

        foreach ($this->exportingData as $key => $value) {
            $this->worksheet->setCellValue('A'.($key + 2), $value[0]);
            $this->worksheet->setCellValue('B'.($key + 2), $value[1]);
            $this->worksheet->setCellValue('C'.($key + 2), $value[2]);
            $this->worksheet->setCellValue('D'.($key + 2), $value[3]);
            $this->worksheet->setCellValue('E'.($key + 2), $value[4]);
        }

        foreach ($this->worksheet->getColumnIterator() as $column) {
            $this->worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }

    public function setExportingData(): void {
        $this->exportingData = $this->userData->map(function (User $user): array {
            $roles = $user->roles->pluck('name')->implode(', ');
            $permissions = $user->getAllPermissions()->pluck('name')->implode(', ');

            return [
                $user->name,
                $user->email,
                $roles,
                $permissions,
                $this->convertUtcDateToTimezone($user->created_at),
            ];
        });
    }

    /**
     * @param Collection<int, User> $userData
     */
    public function setUserData(Collection $userData): void {
        $this->userData = $userData;
    }

    public function getTemplatePath(): string {
        return resource_path('templates/export_user_permissions_template.xlsx');
    }

    public function getPrefix(): string {
        return 'UserPermissionExport';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportDataToArray(): array {
        if ($this->userData->isEmpty()) {
            return [];
        }
        // TODO: support some form of pagination to limit the data to be exported using json
        // transform exporting data to JSON structure for user permission export
        $jsonDataTransform = $this->userData->map(function (User $user): array {
            $roles = $user->roles->map(fn ($role): array => [
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->all(),
            ])->all();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'roles' => $roles,
                'all_permissions' => $user->getAllPermissions()->pluck('name')->all(),
                'created_at' => $this->convertUtcDateToTimezone($user->created_at),
                'updated_at' => $this->convertUtcDateToTimezone($user->updated_at),
            ];
        });

        return $jsonDataTransform->all();
    }
}
