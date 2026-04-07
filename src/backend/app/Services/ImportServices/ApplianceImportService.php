<?php

namespace App\Services\ImportServices;

use App\Models\Appliance;
use App\Models\ApplianceType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplianceImportService extends AbstractImportService {
    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function import(array $data): array {
        $importData = $data;
        if (isset($data['data']) && is_array($data['data'])) {
            $importData = $data['data'];
        }

        $errors = $this->validate($importData);
        if ($errors !== []) {
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        $imported = [];
        $failed = [];

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($importData as $applianceData) {
                try {
                    $result = $this->importAppliance($applianceData);
                    if ($result['success']) {
                        $imported[] = $result['appliance'];
                    } else {
                        $failed[] = [
                            'name' => $applianceData['appliance_name'] ?? 'unknown',
                            'errors' => $result['errors'],
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Error importing appliance', [
                        'name' => $applianceData['appliance_name'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                    $failed[] = [
                        'name' => $applianceData['appliance_name'] ?? 'unknown',
                        'errors' => ['import' => $e->getMessage()],
                    ];
                }
            }

            DB::connection('tenant')->commit();

            $allFailed = count($imported) === 0 && count($failed) > 0;
            $partitioned = $this->partitionResults($imported);

            return [
                'success' => !$allFailed,
                'message' => $allFailed ? 'All appliance imports failed' : 'Appliances imported successfully',
                'imported_count' => count($imported),
                'added_count' => $partitioned['added_count'],
                'modified_count' => $partitioned['modified_count'],
                'failed_count' => count($failed),
                'added' => $partitioned['added'],
                'modified' => $partitioned['modified'],
                'failed' => $failed,
            ];
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('Error during appliance import transaction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'errors' => ['transaction' => 'Failed to import appliances: '.$e->getMessage()],
            ];
        }
    }

    /**
     * @param array<string, mixed> $applianceData
     *
     * @return array<string, mixed>
     */
    private function importAppliance(array $applianceData): array {
        $applianceTypeName = $applianceData['appliance_type'] ?? null;
        $applianceType = null;

        if ($applianceTypeName !== null && $applianceTypeName !== '') {
            $applianceType = ApplianceType::query()->where('name', $applianceTypeName)->first();
            if ($applianceType === null) {
                $applianceType = ApplianceType::query()->create([
                    'name' => $applianceTypeName,
                ]);
            }
        }

        $price = $applianceData['price'] ?? 0;
        if (is_string($price)) {
            $price = (int) str_replace([',', ' '], '', $price);
        }

        $appliance = Appliance::query()
            ->where('name', $applianceData['appliance_name'])
            ->first();
        $isNew = $appliance === null;

        if ($isNew) {
            $appliance = Appliance::query()->create([
                'name' => $applianceData['appliance_name'],
                'appliance_type_id' => $applianceType?->id,
                'price' => $price,
            ]);
        } else {
            $appliance->update([
                'appliance_type_id' => $applianceType !== null ? $applianceType->id : $appliance->appliance_type_id,
                'price' => $price,
            ]);
        }

        return [
            'success' => true,
            'action' => $isNew ? 'added' : 'modified',
            'appliance' => [
                'id' => $appliance->id,
                'name' => $appliance->name,
                'action' => $isNew ? 'added' : 'modified',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    public function validate(array $data): array {
        $errors = [];

        foreach ($data as $index => $applianceData) {
            if (!is_array($applianceData)) {
                $errors["appliance_{$index}"] = 'Appliance data must be an array';
                continue;
            }

            if (empty($applianceData['appliance_name'])) {
                $errors["appliance_{$index}.appliance_name"] = 'Appliance name is required';
            }
        }

        return $errors;
    }
}
