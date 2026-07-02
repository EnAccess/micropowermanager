<?php

namespace App\Services\ImportServices;

use App\Models\Appliance;
use App\Models\ApplianceType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplianceImportService extends AbstractImportService {
    /**
     * @param list<array<string, mixed>> $data
     */
    public function import(array $data): ImportResult {
        $imported = [];
        $failed = [];

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($data as $applianceData) {
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

            return new ImportResult(
                message: $allFailed ? 'All appliance imports failed' : 'Appliances imported successfully',
                added: $partitioned['added'],
                modified: $partitioned['modified'],
                failed: $failed,
            );
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->throwTransactionFailure('appliances', $e);
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
}
