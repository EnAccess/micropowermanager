<?php

namespace App\Services\ImportServices;

use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\Device;
use App\Models\Person\Person;
use App\Models\User;
use App\Services\AppliancePersonService;
use App\Services\ApplianceRateService;
use App\Services\UserAppliancePersonService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @extends AbstractImportService<AppliancePersonImportItem>
 */
class AppliancePersonImportService extends AbstractImportService {
    /** @var array<int, User|null> */
    private array $creatorCache = [];

    public function __construct(
        private AppliancePersonService $appliancePersonService,
        private ApplianceRateService $applianceRateService,
        private UserAppliancePersonService $userAppliancePersonService,
    ) {}

    /**
     * @param list<AppliancePersonImportItem> $data
     */
    public function import(array $data): ImportResult {
        $imported = [];
        $failed = [];

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($data as $item) {
                try {
                    $result = $this->importAppliancePerson($item);
                    if ($result['success']) {
                        $imported[] = $result['appliance_person'];
                    } else {
                        $failed[] = [
                            'name' => $this->label($item),
                            'errors' => $result['errors'],
                        ];
                    }
                } catch (\Exception $exception) {
                    Log::error('Error importing AppliancePerson', [
                        'appliance_person' => $this->label($item),
                        'error' => $exception->getMessage(),
                    ]);
                    $failed[] = [
                        'name' => $this->label($item),
                        'errors' => ['import' => $exception->getMessage()],
                    ];
                }
            }

            DB::connection('tenant')->commit();

            $allFailed = count($imported) === 0 && count($failed) > 0;
            $partitioned = $this->partitionResults($imported);

            return new ImportResult(
                message: $allFailed ? 'All AppliancePerson imports failed' : 'AppliancePerson records imported successfully',
                added: $partitioned['added'],
                modified: $partitioned['modified'],
                failed: $failed,
            );
        } catch (\Exception $exception) {
            DB::connection('tenant')->rollBack();
            $this->throwTransactionFailure('AppliancePerson records', $exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function importAppliancePerson(AppliancePersonImportItem $item): array {
        $person = Person::query()
            ->where('name', $item->customerName)
            ->where('surname', $item->customerSurname)
            ->first();

        if ($person === null) {
            return $this->failure(['customer' => "Customer '{$this->label($item)}' was not found; import the customer first"]);
        }

        $appliance = Appliance::query()->where('name', $item->applianceName)->first();

        if ($appliance === null) {
            return $this->failure(['appliance' => "Appliance '{$item->applianceName}' was not found; import the appliance first"]);
        }

        if ($item->deviceSerial !== null && $item->deviceSerial !== '' && $this->appliancePersonService->getBySerialNumber($item->deviceSerial) instanceof AppliancePerson) {
            return $this->failure(['device_serial' => "An AppliancePerson already exists for device '{$item->deviceSerial}'"]);
        }

        $creator = $this->resolveCreator($item->creatorId);

        if (!$creator instanceof User) {
            return $this->failure(['creator' => 'The importing user could not be resolved']);
        }

        $isEnergyService = $item->paymentType === AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE;

        if (!$isEnergyService && ($item->totalCost === null || $item->totalCost <= 0 || $item->rateCount === null || $item->rateCount < 1)) {
            return $this->failure(['installment' => 'An installment payment_type requires a positive total_cost and a rate_count of at least 1']);
        }

        $appliancePerson = $this->appliancePersonService->make([
            'appliance_id' => $appliance->id,
            'person_id' => $person->id,
            'total_cost' => $isEnergyService ? 0 : $item->totalCost,
            'rate_count' => $isEnergyService ? 0 : $item->rateCount,
            'down_payment' => $item->downPayment ?? 0,
            'first_payment_date' => $item->firstPaymentDate,
            'device_serial' => $item->deviceSerial,
            'payment_type' => $item->paymentType,
            'minimum_payable_amount' => $isEnergyService ? $item->minimumPayableAmount : null,
            'price_per_day' => $isEnergyService ? $item->pricePerDay : null,
        ]);

        $this->userAppliancePersonService->setAssigned($appliancePerson);
        $this->userAppliancePersonService->setAssignee($creator);
        $this->userAppliancePersonService->assign();
        $this->appliancePersonService->save($appliancePerson);

        if (!$appliancePerson->isEnergyService()) {
            $this->applianceRateService->create($appliancePerson, $item->rateType ?? 'monthly');
        }

        if ($item->deviceSerial !== null && $item->deviceSerial !== '') {
            Device::query()
                ->where('device_serial', $item->deviceSerial)
                ->update(['person_id' => $person->id]);
        }

        return [
            'success' => true,
            'action' => 'added',
            'appliance_person' => [
                'id' => $appliancePerson->id,
                'customer' => $this->label($item),
                'appliance' => $appliance->name,
                'device_serial' => $item->deviceSerial,
                'action' => 'added',
            ],
        ];
    }

    private function resolveCreator(int $creatorId): ?User {
        return $this->creatorCache[$creatorId] ??= User::query()->find($creatorId);
    }

    /**
     * @param array<string, string> $errors
     *
     * @return array{success: false, errors: array<string, string>}
     */
    private function failure(array $errors): array {
        return ['success' => false, 'errors' => $errors];
    }

    private function label(AppliancePersonImportItem $item): string {
        return trim($item->customerName.' '.$item->customerSurname);
    }
}
