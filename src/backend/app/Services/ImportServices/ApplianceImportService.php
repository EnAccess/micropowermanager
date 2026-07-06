<?php

namespace App\Services\ImportServices;

use App\Models\Appliance;
use App\Models\ApplianceType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @extends AbstractImportService<ApplianceImportItem>
 */
class ApplianceImportService extends AbstractImportService {
    /**
     * @param list<ApplianceImportItem> $data
     */
    public function import(array $data): ImportResult {
        $imported = [];
        $failed = [];

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($data as $item) {
                try {
                    $result = $this->importAppliance($item);
                    if ($result['success']) {
                        $imported[] = $result['appliance'];
                    } else {
                        $failed[] = [
                            'name' => $item->applianceName,
                            'errors' => $result['errors'],
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Error importing appliance', [
                        'name' => $item->applianceName,
                        'error' => $e->getMessage(),
                    ]);
                    $failed[] = [
                        'name' => $item->applianceName,
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
     * @return array<string, mixed>
     */
    private function importAppliance(ApplianceImportItem $item): array {
        $applianceType = null;
        if ($item->applianceType !== null && $item->applianceType !== '') {
            $applianceType = ApplianceType::query()->where('name', $item->applianceType)->first();
            if ($applianceType === null) {
                $applianceType = ApplianceType::query()->create([
                    'name' => $item->applianceType,
                ]);
            }
        }

        $appliance = Appliance::query()
            ->where('name', $item->applianceName)
            ->first();
        $isNew = $appliance === null;

        if ($isNew) {
            $appliance = Appliance::query()->create([
                'name' => $item->applianceName,
                'appliance_type_id' => $applianceType?->id,
                'price' => $item->price,
            ]);
        } else {
            $appliance->update([
                'appliance_type_id' => $applianceType !== null ? $applianceType->id : $appliance->appliance_type_id,
                'price' => $item->price,
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
