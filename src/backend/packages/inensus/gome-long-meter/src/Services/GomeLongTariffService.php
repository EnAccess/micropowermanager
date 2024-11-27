<?php

namespace Inensus\GomeLongMeter\Services;

use App\Models\MainSettings;
use App\Models\Meter\MeterTariff;
use Illuminate\Support\Facades\Log;
use Inensus\GomeLongMeter\Models\GomeLongTariff;
use Inensus\GomeLongMeter\Modules\Api\ApiRequests;

class GomeLongTariffService {
    public const API_CALL_TARIFF_LIST = '/UTSearch';
    public const API_CALL_TARIFF_CREATE = '/UTAdd';
    public const API_CALL_TARIFF_UPDATE = '/UTUpdate';
    public const API_CALL_TARIFF_DELETE = '/UTdelete';
    public const ELECTRICITY_TARIFF_TYPE = 1;

    public function __construct(
        private ApiRequests $apiRequests,
        private MeterTariff $meterTariff,
        private GomeLongTariff $gomeLongTariff,
        private GomeLongCredentialService $credentialService,
        private MainSettings $mainSettings,
    ) {}

    public function getByMpmTariffId($mpmTariffId) {
        return $this->gomeLongTariff->where('mpm_tariff_id', $mpmTariffId)->first();
    }

    public function createGomeLongTariff($tariff) {
        $gomeLongTariff = $this->getByMpmTariffId($tariff->id);

        if (!$gomeLongTariff) {
            $credentials = $this->credentialService->getCredentials();
            $vatEnergy = $this->mainSettings->newQuery()->first()->vat_energy;
            $params = [
                'U' => $credentials->getUserId(),
                'K' => $credentials->getUserPassword(),
                'type' => 'e',
                'VAT' => $vatEnergy,
                'Name' => $tariff->name,
                'Price' => $tariff->total_price,
            ];
            $gomeLongTariff = $this->apiRequests->post($credentials, $params, self::API_CALL_TARIFF_CREATE);

            $this->gomeLongTariff->newQuery()->create([
                'tariff_id' => $gomeLongTariff['FID'],
                'mpm_tariff_id' => $tariff->id,
                'price' => $tariff->total_price,
                'vat' => $vatEnergy,
            ]);
        }
    }

    public function updateGomeLongTariff($tariff) {
        try {
            $gomeLongTariff = $this->getByMpmTariffId($tariff->id);

            if (!$gomeLongTariff) {
                return true;
            }

            $tariffId = $gomeLongTariff->getTariffId();
            $credentials = $this->credentialService->getCredentials();
            $params = [
                'U' => $credentials->getUserId(),
                'K' => $credentials->getUserPassword(),
                'ID' => $tariffId,
                'VAT' => $this->mainSettings->newQuery()->first()->vat_energy,
                'Name' => $tariff->name,
                'Price' => $tariff->total_price,
            ];

            return $this->apiRequests->post($credentials, $params, self::API_CALL_TARIFF_UPDATE);
        } catch (\Exception $e) {
            Log::critical(
                'updating tariff info from GomeLong Meter API failed.',
                ['Error :' => $e->getMessage()]
            );
            throw new \Exception($e->getMessage());
        }
    }

    public function deleteGomeLongTariff($tariff) {
        try {
            $gomeLongTariff = $this->getByMpmTariffId($tariff->id);

            if ($gomeLongTariff) {
                return true;
            }

            $tariffId = $gomeLongTariff->getTariffId();
            $credentials = $this->credentialService->getCredentials();
            $params = [
                'U' => $credentials->getUserId(),
                'K' => $credentials->getUserPassword(),
                'ID' => $tariffId,
            ];

            return $this->apiRequests->post($credentials, $params, self::API_CALL_TARIFF_DELETE);
        } catch (\Exception $e) {
            Log::critical(
                'updating tariff info from GomeLong Meter API failed.',
                ['Error :' => $e->getMessage()]
            );
            throw new \Exception($e->getMessage());
        }
    }

    public function sync() {
        try {
            $credentials = $this->credentialService->getCredentials();

            if (!$credentials || ($credentials->getUserId() === null
                    || $credentials->getUserPassword() === null)) {
                return true;
            }

            $params = [
                'U' => $credentials->getUserId(),
                'K' => $credentials->getUserPassword(),
            ];
            $gomeLongTariffs = $this->apiRequests->post($credentials, $params, self::API_CALL_TARIFF_LIST);

            foreach ($gomeLongTariffs as $gomeLongTariff) {
                if ($gomeLongTariff['FMeterType'] !== self::ELECTRICITY_TARIFF_TYPE) {
                    continue;
                }

                $registeredGomeLongTariff = $this->gomeLongTariff->newQuery()
                    ->with('mpmTariff')
                    ->where('tariff_id', $gomeLongTariff['FID'])->first();

                if ($registeredGomeLongTariff) {
                    $meterTariff = $registeredGomeLongTariff->mpmTariff;

                    if ($meterTariff->name !== $gomeLongTariff['FName']
                        || $meterTariff->total_price !== $gomeLongTariff['FPrice']) {
                        $meterTariff->update([
                            'name' => $gomeLongTariff['FName'],
                            'price' => $gomeLongTariff['FPrice'],
                            'total_price' => $gomeLongTariff['FPrice'],
                        ]);
                    }
                    $registeredGomeLongTariff->update([
                        'vat' => $gomeLongTariff['FVAT'],
                    ]);
                } else {
                    $IncidentModel = new MeterTariff();
                    $IncidentModel->unsetEventDispatcher();
                    $meterTariff = $IncidentModel->create([
                        'name' => $gomeLongTariff['FName'],
                        'price' => $gomeLongTariff['FPrice'],
                        'currency' => $this->mainSettings->newQuery()->first()->currency,
                        'total_price' => $gomeLongTariff['FPrice'],
                    ]);
                    $this->gomeLongTariff->newQuery()->create([
                        'tariff_id' => $gomeLongTariff['FID'],
                        'mpm_tariff_id' => $meterTariff->id,
                        'vat' => $gomeLongTariff['FVAT'],
                    ]);
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::critical(
                'syncing tariff info from gomelong api failed.',
                ['Error :' => $e->getMessage()]
            );
            throw new \Exception($e->getMessage());
        }
    }
}
