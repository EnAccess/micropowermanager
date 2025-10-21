<?php

namespace Inensus\KelinMeter\Services;

use App\Models\Address\Address;
use App\Models\ConnectionGroup;
use App\Models\ConnectionType;
use App\Models\GeographicalInformation;
use App\Models\MainSettings;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterType;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inensus\KelinMeter\Exceptions\KelinApiResponseException;
use Inensus\KelinMeter\Helpers\ApiHelpers;
use Inensus\KelinMeter\Http\Clients\KelinMeterApiClient;
use Inensus\KelinMeter\Models\KelinCustomer;
use Inensus\KelinMeter\Models\KelinMeter;
use Inensus\KelinMeter\Models\SyncStatus;

/**
 * @implements ISynchronizeService<KelinMeter>
 */
class KelinMeterService implements ISynchronizeService {
    private string $rootUrl = '/listMeter';
    /**
     * @var Collection<int, array{meter_serial: string, id: int}>
     */
    private Collection $earlyRegisteredMeters;

    public function __construct(
        private Meter $meter,
        private KelinMeter $kelinMeter,
        private KelinMeterApiClient $kelinApiClient,
        private ApiHelpers $apiHelpers,
        private KelinSyncSettingService $syncSettingService,
        private KelinSyncActionService $syncActionService,
        private KelinCustomer $kelinCustomer,
        private Manufacturer $manufacturer,
        private ConnectionGroup $connectionGroup,
        private ConnectionType $connectionType,
        private MeterTariff $meterTariff,
    ) {}

    /**
     * @return LengthAwarePaginator<int, KelinMeter>
     */
    public function getMeters(Request $request): LengthAwarePaginator {
        $perPage = (int) $request->input('per_page', 15);

        return $this->kelinMeter->newQuery()->with([
            'mpmMeter',
            'kelinCustomer.mpmPerson',
        ])->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, KelinMeter>
     */
    public function sync(): LengthAwarePaginator {
        $synSetting = $this->syncSettingService->getSyncSettingsByActionName('Meters');
        $syncAction = $this->syncActionService->getSyncActionBySynSettingId($synSetting->id);
        try {
            $syncCheck = $this->syncCheck(true);
            $syncCheck['data']
                ->filter(fn (array $value): bool => $value['syncStatus'] === SyncStatus::NOT_REGISTERED_YET)
                ->each(function (array $meter) {
                    $createdMeter = $this->createRelatedMeter($meter);
                    $this->kelinMeter->newQuery()->create([
                        'meter_name' => $meter['meterName'],
                        'meter_address' => $meter['meterAddr'],
                        'customer_no' => $meter['consNo'],
                        'rtuId' => $meter['rtuId'],
                        'mpm_meter_id' => $createdMeter->id,
                        'hash' => $meter['hash'],
                    ]);
                });
            $syncCheck['data']
                ->filter(fn (array $value): bool => $value['syncStatus'] === SyncStatus::EARLY_REGISTERED)
                ->each(function (array $meter) {
                    $updatedMeter = $this->updateRelatedMeter(
                        $meter,
                        $meter['relatedMeter']
                    );
                    $this->kelinMeter->newQuery()->create([
                        'meter_name' => $meter['meterName'],
                        'meter_address' => $meter['meterAddr'],
                        'customer_no' => $meter['consNo'],
                        'mpm_meter_id' => $updatedMeter->id,
                        'hash' => $meter['hash'],
                    ]);
                });
            $syncCheck['data']
                ->filter(fn (array $value): bool => $value['syncStatus'] === SyncStatus::MODIFIED)
                ->each(function (array $meter) {
                    $relatedMeter = is_null($meter['relatedMeter']) ?
                        $this->createRelatedMeter($meter) : $this->updateRelatedMeter($meter, $meter['relatedMeter']);
                    $meter['registeredKelinMeter']->update([
                        'meter_name' => $meter['meterName'],
                        'meter_address' => $meter['meterAddr'],
                        'customer_no' => $meter['consNo'],
                        'mpm_meter_id' => $relatedMeter->id,
                        'hash' => $meter['hash'],
                    ]);
                });
            $this->syncActionService->updateSyncAction($syncAction, $synSetting, true);

            return $this->kelinMeter->newQuery()->with([
                'mpmMeter',
                'kelinCustomer.mpmPerson',
            ])->paginate(config('kelin-meter.paginate'));
        } catch (\Exception $e) {
            $this->syncActionService->updateSyncAction($syncAction, $synSetting, false);
            Log::critical('Kelin meters sync failed.', ['Error :' => $e->getMessage()]);
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    // @phpstan-ignore missingType.iterableValue
    public function syncCheck(bool $returnData = false): array {
        try {
            $url = $this->rootUrl;
            $result = $this->kelinApiClient->get($url);
            $meters = $result['data'];
        } catch (KelinApiResponseException $exception) {
            if ($returnData) {
                return ['result' => false];
            }
            throw new KelinApiResponseException($exception->getMessage());
        }

        $metersCollection = collect($meters)->filter(fn (array $meter): bool => $meter['consNo'] !== null);

        $kelinMeters = $this->kelinMeter->newQuery()->get();
        $this->getEarlyRegisteredMetersWithChangeSerialNumbersAsSimilarAsKalinMeterData();
        $meters = $this->meter->newQuery()->get();
        $metersCollection->transform(function ($meter) use ($kelinMeters, $meters): array {
            $meterHash = $this->kelinMeterHasher($meter);
            $earlyRegisteredMeter = $this->findRegisteredMeter($meter);
            $registeredStmMeter = $kelinMeters->firstWhere('meter_address', $meter['meterAddr']);
            if ($earlyRegisteredMeter instanceof Meter) {
                $meter['hash'] = $meterHash;
                $meter['syncStatus'] = SyncStatus::EARLY_REGISTERED;
                if ($registeredStmMeter) {
                    $meter['syncStatus'] = $meterHash === $registeredStmMeter->hash ?
                        SyncStatus::SYNCED : SyncStatus::MODIFIED;
                }
                $meter['relatedMeter'] = $earlyRegisteredMeter;
                $meter['registeredKelinMeter'] = null;

                return $meter;
            } else {
                $relatedMeter = null;
                if ($registeredStmMeter) {
                    $meter['syncStatus'] = $meterHash === $registeredStmMeter->hash ?
                        SyncStatus::SYNCED : SyncStatus::MODIFIED;
                    $relatedMeter = $meters->where('id', $registeredStmMeter->mpm_meter_id)->first();
                } else {
                    $meter['syncStatus'] = SyncStatus::NOT_REGISTERED_YET;
                }
                $meter['hash'] = $meterHash;
                $meter['relatedMeter'] = $relatedMeter;
                $meter['registeredKelinMeter'] = $registeredStmMeter;

                return $meter;
            }
        });
        $meterSyncStatus = $metersCollection->whereNotIn('syncStatus', [SyncStatus::SYNCED])->count();
        if ($meterSyncStatus) {
            return $returnData ? ['data' => $metersCollection, 'result' => false] : ['result' => false];
        }

        return $returnData ? ['data' => $metersCollection, 'result' => true] : ['result' => true];
    }

    /**
     * @param array<string, mixed> $kelinMeter
     */
    private function kelinMeterHasher(array $kelinMeter): string {
        return $this->apiHelpers->makeHash([
            $kelinMeter['consNo'],
            $kelinMeter['meterAddr'],
            $kelinMeter['meterType'],
            $kelinMeter['meterName'],
            $kelinMeter['mpId'],
            $kelinMeter['pt'],
            $kelinMeter['rtuId'],
            $kelinMeter['ct'],
            $kelinMeter['useFlag'],
        ]);
    }

    /**
     * @param array<string, mixed> $kelinMeter
     */
    public function createRelatedMeter(array $kelinMeter): Meter {
        try {
            DB::connection('tenant')->beginTransaction();
            $meterSerial = $this->generateMeterSerialNumberInFormat($kelinMeter['meterAddr']);

            $meter = $this->meter->newQuery()->where('serial_number', $meterSerial)->first();
            $kelinCustomer = $this->kelinCustomer->newQuery()->with('mpmPerson')->where(
                'customer_no',
                $kelinMeter['consNo']
            )->first();
            if ($meter === null) {
                $meter = new Meter();
                $geoLocation = new GeographicalInformation();
            } else {
                $geoLocation = $meter->device->person->addresses()->first()->geo->first();
                if ($geoLocation === null) {
                    $geoLocation = new GeographicalInformation();
                }
            }
            $meter->serial_number = $meterSerial;
            $manufacturer = $this->manufacturer->newQuery()->where('name', 'Kelin Meters')->firstOrFail();
            $meter->manufacturer()->associate($manufacturer);
            $meter->updated_at = date('Y-m-d h:i:s');
            $meterType = MeterType::query()->first();
            $meter->meter_type_id = $meterType ? $meterType->id : 1;
            $meter->save();

            if ($kelinCustomer) {
                // $geographicalCoordinatesResult = $this->geographicalLocationFinder->getCoordinatesGivenAddress($kelinCustomer->address);
                // $geoLocation->points = $geographicalCoordinatesResult['lat'] . ',' . $geographicalCoordinatesResult['lng'];

                $points = $kelinCustomer->mpmPerson->addresses[0]->geo->points;
                $p = $points == null ? ',' : $kelinCustomer->mpmPerson->addresses[0];
                $geoLocation->points = $p;
                $connectionType = $this->connectionType->newQuery()->first();
                if (!$connectionType) {
                    $connectionType = $this->connectionType->newQuery()->create([
                        'name' => 'default',
                    ]);
                }
                $connectionGroup = $this->connectionGroup->newQuery()->first();
                if (!$connectionGroup) {
                    $connectionGroup = $this->connectionGroup->newQuery()->create([
                        'name' => 'default',
                    ]);
                }
                $meter->connection_type_id = $connectionType->id;
                $meter->connection_group_id = $connectionGroup->id;
                $meter->device->person()->associate($kelinCustomer->mpmPerson);
                $tariff = $this->meterTariff->newQuery()->firstOrCreate(['id' => 1], [
                    'name' => 'Automatically Created Tariff',
                    'price' => 0,
                    'currency' => MainSettings::query()->first() ? MainSettings::query()->first()->currency : '',
                ]);
                $meter->tariff()->associate($tariff);
                $meter->save();
                $kelinCustomerAddress = $kelinCustomer
                    ->mpmPerson
                    ->with('addresses.city')
                    ->whereHas('addresses', fn ($q) => $q->where('is_primary', 1))
                    ->first()
                ;

                $city = $kelinCustomerAddress->addresses[0]->city()->first() ?? null;
                $address = new Address();
                $address = $address->newQuery()->create([
                    'city_id' => $city ? $city->id : 1,
                ]);
                $address->owner()->associate($meter);
                $address->geo()->save($meter->device->address->geo()->first());
                $address->save();
            }
            DB::connection('tenant')->commit();

            return $meter;
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::critical('Error while synchronizing kelin meters', ['message' => $e->getMessage()]);
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param array<string, mixed> $kelinMeter
     */
    public function updateRelatedMeter(array $kelinMeter, Meter $meter): Meter {
        $kelinCustomer = $this->kelinCustomer->newQuery()->with('mpmPerson')->where(
            'customer_no',
            $kelinMeter['consNo']
        )->first();
        if ($kelinCustomer) {
            // commented out because of address definition type changed on kelin side
            //   $geographicalCoordinatesResult = $this->geographicalLocationFinder->getCoordinatesGivenAddress($kelinCustomer->address);
            //   $points = $geographicalCoordinatesResult['lat'] . ',' . $geographicalCoordinatesResult['lng'];
            $points = $kelinCustomer->address ?? ',';
            $meter->device->person()->associate($kelinCustomer->mpmPerson);
            $p = $points == null ? '' : $points;
            $meter->device->person->addresses()->first()->geo()->update([
                'points' => $p,
            ]);
            $meter->save();
        }

        return $meter;
    }

    /**
     * @param array<string, mixed> $kelinMeter
     */
    private function findRegisteredMeter(array $kelinMeter): ?Meter {
        $meter = collect($this->earlyRegisteredMeters)->where('meter_serial', $kelinMeter['meterAddr'])->first();
        if (!$meter) {
            return $meter;
        }

        return $this->meter->newQuery()->find($meter['id']);
    }

    private function getEarlyRegisteredMetersWithChangeSerialNumbersAsSimilarAsKalinMeterData(): void {
        $this->earlyRegisteredMeters = $this->meter->newQuery()->get()->map(function ($q): array {
            $string = substr($q->serial_number, 0, -2);
            $array = explode('-', $string);
            $serial = implode('', $array);

            return [
                'meter_serial' => $serial,
                'id' => $q->id,
            ];
        });
    }

    private function generateMeterSerialNumberInFormat(string $meterAddress): string {
        $length = strlen($meterAddress);
        $newSerial = $meterAddress[0];
        for ($i = 0; $i < $length; ++$i) {
            if ($i != 0) {
                if ($i % 4 == 0) {
                    $newSerial .= '-'.$meterAddress[$i];
                } else {
                    $newSerial .= $meterAddress[$i];
                }
            }
        }

        return $newSerial.('-'.random_int(0, 9));
    }
}
