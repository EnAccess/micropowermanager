<?php

namespace App\Plugins\StsMeterVend\Services;

use App\Models\Device;
use App\Models\Meter\Meter;
use App\Plugins\BulkRegistration\Helpers\CsvFileParser;
use App\Plugins\StsMeterVend\Enums\StsBaseDate;
use App\Plugins\StsMeterVend\Enums\StsEncryptionAlgorithm;
use App\Plugins\StsMeterVend\Http\Clients\StsApiClient;
use App\Plugins\StsMeterVend\Models\StsMeter;
use App\Plugins\StsMeterVend\Modules\Api\StsMeterApi;
use App\Services\DeviceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

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
        private StsApiClient $client,
    ) {}

    /**
     * @return array{imported: int, skipped: list<array{serial: string, reason: string}>}
     */
    public function importCsv(UploadedFile $file): array {
        $rows = $this->csvFileParser->parseCsvFromFilePath($file->getRealPath());
        $imported = 0;
        $skipped = [];

        foreach ($rows as $row) {
            $serial = trim((string) ($row['serial'] ?? ''));
            $decoderKey = trim((string) ($row['decoder_key'] ?? ''));
            $baseDate = $this->column($row, 'base_date', self::DEFAULT_BASE_DATE->value);
            $encryptionAlgorithm = strtoupper($this->column($row, 'encryption_algorithm', self::DEFAULT_ENCRYPTION_ALGORITHM->value));
            $keyExpiryNumber = $this->column($row, 'key_expiry_number', (string) self::DEFAULT_KEY_EXPIRY_NUMBER);
            $device = $serial === '' ? null : $this->deviceService->getBySerialNumber($serial);

            $reason = $this->rejectionReason($serial, $decoderKey, $baseDate, $encryptionAlgorithm, $keyExpiryNumber, $device);
            if ($reason !== null) {
                $skipped[] = ['serial' => $serial, 'reason' => $reason];
                continue;
            }

            $this->stsMeter->newQuery()->updateOrCreate(
                ['mpm_meter_id' => $device->device->id],
                [
                    'decoder_key' => strtoupper($decoderKey),
                    'base_date' => StsBaseDate::from($baseDate),
                    'encryption_algorithm' => StsEncryptionAlgorithm::from($encryptionAlgorithm),
                    'key_expiry_number' => (int) $keyExpiryNumber,
                ],
            );
            ++$imported;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
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
            ->where('vending_provider', StsMeterApi::VENDING_PROVIDER)
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
        string $serial,
        string $decoderKey,
        string $baseDate,
        string $encryptionAlgorithm,
        string $keyExpiryNumber,
        ?Device $device,
    ): ?string {
        if ($serial === '') {
            return 'Missing serial.';
        }

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

        if (!$device instanceof Device || !$device->device instanceof Meter) {
            return 'No meter registered with this serial.';
        }

        if ($device->device->vending_provider !== StsMeterApi::VENDING_PROVIDER) {
            return 'Meter is not vended through Native STS; set its vending provider first.';
        }

        return null;
    }
}
