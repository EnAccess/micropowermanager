<?php

namespace App\Plugins\StsMeterVend\Services;

use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\MpmPlugin;
use App\Plugins\BulkRegistration\Helpers\CsvFileParser;
use App\Plugins\StsMeterVend\Enums\StsBaseDate;
use App\Plugins\StsMeterVend\Enums\StsEncryptionAlgorithm;
use App\Plugins\StsMeterVend\Http\Clients\StsApiClient;
use App\Plugins\StsMeterVend\Models\StsMeter;
use App\Services\DeviceControlService;
use App\Services\DeviceService;
use App\Services\ImportServices\DeviceImportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Owns the OEM-supplied decoder key of each STS meter together with the key
 * profile it was loaded with. Only profiles the engine can vend are accepted,
 * so a meter that would reject every token never becomes vendable.
 */
class StsMeterKeyService {
    private const string DECODER_KEY_PATTERN = '/^[0-9A-Fa-f]{16}$/';
    private const StsBaseDate DEFAULT_BASE_DATE = StsBaseDate::Year2014;
    private const StsEncryptionAlgorithm DEFAULT_ENCRYPTION_ALGORITHM = StsEncryptionAlgorithm::Sta;
    private const int DEFAULT_KEY_EXPIRY_NUMBER = 255;

    public function __construct(
        private StsMeter $stsMeter,
        private CsvFileParser $csvFileParser,
        private DeviceService $deviceService,
        private DeviceControlService $deviceControlService,
        private DeviceImportService $deviceImportService,
        private StsApiClient $client,
    ) {}

    /**
     * Rows whose serial has no meter are listed as unregistered. When a manufacturer
     * and tariff are given, those meters are created instead, but only for rows whose
     * key is accepted.
     *
     * @return array{imported: int, created: int, skipped: list<array{serial: string, reason: string}>, unregistered: list<string>}
     */
    public function importCsv(UploadedFile $file, ?int $manufacturerId = null, ?int $tariffId = null): array {
        $rows = $this->csvFileParser->parseCsvFromFilePath($file->getRealPath());
        $imported = 0;
        $created = 0;
        $skipped = [];
        $unregistered = [];

        foreach ($rows as $row) {
            $serial = trim((string) ($row['serial'] ?? ''));

            if ($serial === '') {
                $skipped[] = ['serial' => $serial, 'reason' => 'Missing serial.'];
                continue;
            }

            $device = $this->deviceService->getBySerialNumber($serial);
            $isNewMeter = !$device instanceof Device;

            if ($isNewMeter && ($manufacturerId === null || $tariffId === null)) {
                $skipped[] = ['serial' => $serial, 'reason' => 'No meter registered with this serial.'];
                $unregistered[] = $serial;
                continue;
            }

            if (!$isNewMeter && !$device->device instanceof Meter) {
                $skipped[] = ['serial' => $serial, 'reason' => 'Not a meter.'];
                continue;
            }

            $keyRecord = [
                'decoder_key' => trim((string) ($row['decoder_key'] ?? '')),
                'base_date' => $this->column($row, 'base_date', self::DEFAULT_BASE_DATE->value),
                'encryption_algorithm' => strtoupper($this->column($row, 'encryption_algorithm', self::DEFAULT_ENCRYPTION_ALGORITHM->value)),
                'key_expiry_number' => $this->column($row, 'key_expiry_number', (string) self::DEFAULT_KEY_EXPIRY_NUMBER),
            ];

            try {
                // A key on file is what makes a meter vendable here, so a created meter,
                // its key and the switch to this plugin are written together or not at all.
                DB::connection('tenant')->transaction(function () use ($device, $serial, $manufacturerId, $tariffId, $keyRecord): void {
                    $device ??= $this->deviceImportService->createMeter($serial, $manufacturerId, $tariffId);
                    $this->store($device->device, $keyRecord);
                    $this->deviceControlService->updateVendingPlugin($device, MpmPlugin::STS_METER_VEND);
                    $this->deviceControlService->refreshManufacturerMapping($device);
                });
            } catch (ValidationException $exception) {
                $skipped[] = ['serial' => $serial, 'reason' => $exception->validator->errors()->first()];
                continue;
            }

            if ($isNewMeter) {
                ++$created;
            }
            ++$imported;
        }

        return ['imported' => $imported, 'created' => $created, 'skipped' => $skipped, 'unregistered' => $unregistered];
    }

    /**
     * @param array<string, mixed> $provisioning
     *
     * @throws ValidationException
     */
    private function store(Meter $meter, array $provisioning): void {
        $decoderKey = (string) ($provisioning['decoder_key'] ?? '');
        $baseDate = (string) ($provisioning['base_date'] ?? '');
        $encryptionAlgorithm = (string) ($provisioning['encryption_algorithm'] ?? '');
        $keyExpiryNumber = (string) ($provisioning['key_expiry_number'] ?? '');

        $reason = $this->rejectionReason($decoderKey, $baseDate, $encryptionAlgorithm, $keyExpiryNumber);
        if ($reason !== null) {
            throw ValidationException::withMessages(['decoder_key' => [$reason]]);
        }

        $this->stsMeter->newQuery()->updateOrCreate(
            ['mpm_meter_id' => $meter->id],
            [
                'decoder_key' => strtoupper($decoderKey),
                'base_date' => StsBaseDate::from($baseDate),
                'encryption_algorithm' => StsEncryptionAlgorithm::from($encryptionAlgorithm),
                'key_expiry_number' => (int) $keyExpiryNumber,
            ],
        );
    }

    public function keyFileTemplate(): string {
        $example = ['47000000001', '0123456789ABCDEF', self::DEFAULT_BASE_DATE->value, self::DEFAULT_ENCRYPTION_ALGORITHM->value, self::DEFAULT_KEY_EXPIRY_NUMBER];

        return implode(',', ['serial', 'decoder_key', 'base_date', 'encryption_algorithm', 'key_expiry_number'])."\n".implode(',', $example)."\n";
    }

    /**
     * Each meter carries its key record, or null, as the `stsMeter` relation.
     *
     * @return LengthAwarePaginator<int, Meter>
     */
    public function paginateManagedMeters(int $perPage): LengthAwarePaginator {
        $meters = Meter::query()
            ->whereHas('device', fn (Builder $device) => $device->where('vending_plugin_id', MpmPlugin::STS_METER_VEND))
            ->with(['device.person', 'tariff'])
            ->orderByDesc('id')
            ->paginate($perPage);

        $keys = $this->stsMeter->newQuery()
            ->whereIn('mpm_meter_id', $meters->pluck('id'))
            ->get()
            ->keyBy('mpm_meter_id');

        foreach ($meters as $meter) {
            $meter->setRelation('stsMeter', $keys->get($meter->id));
        }

        return $meters;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function column(array $row, string $name, string $default): string {
        $value = trim((string) ($row[$name] ?? ''));

        return $value === '' ? $default : $value;
    }

    private function rejectionReason(
        string $decoderKey,
        string $baseDate,
        string $encryptionAlgorithm,
        string $keyExpiryNumber,
    ): ?string {
        if (preg_match(self::DECODER_KEY_PATTERN, $decoderKey) !== 1) {
            return 'decoder_key must be exactly 16 hexadecimal characters.';
        }

        if (!StsBaseDate::tryFrom($baseDate) instanceof StsBaseDate) {
            return 'Invalid base_date; expected 1993, 2014 or 2035.';
        }

        if (!StsEncryptionAlgorithm::tryFrom($encryptionAlgorithm) instanceof StsEncryptionAlgorithm) {
            return 'Invalid encryption_algorithm; expected STA, DEA or MISTY1.';
        }

        if (!ctype_digit($keyExpiryNumber) || (int) $keyExpiryNumber > 255) {
            return 'Invalid key_expiry_number; expected a whole number from 0 to 255.';
        }

        if (!$this->client->supportsKeyProfile(StsBaseDate::from($baseDate), StsEncryptionAlgorithm::from($encryptionAlgorithm), (int) $keyExpiryNumber)) {
            return "The token engine cannot vend base date {$baseDate}, algorithm {$encryptionAlgorithm}, key expiry {$keyExpiryNumber} yet.";
        }

        return null;
    }
}
