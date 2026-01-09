<?php

namespace App\Services\ExportServices;

use App\Exceptions\ActiveSheetNotCreatedException;
use App\Exceptions\Export\CsvNotSavedException;
use App\Exceptions\Export\SpreadSheetNotCreatedException;
use App\Exceptions\Export\SpreadSheetNotSavedException;
use App\Models\DatabaseProxy;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class AbstractExportService {
    protected IReader $reader;
    protected Worksheet $worksheet;
    protected Spreadsheet $spreadsheet;
    /** @var Collection<int, mixed> */
    protected Collection $exportingData;
    protected string $currency;
    protected string $timeZone;

    abstract public function setExportingData(): void;

    abstract public function getTemplatePath(): string;

    abstract public function getPrefix(): string;

    public function createSpreadSheetFromTemplate(string $path): Spreadsheet {
        try {
            $this->reader = IOFactory::createReader('Xlsx');
            $this->spreadsheet =
                $this->reader->load($path);
            $this->reader->setIncludeCharts(true);

            return $this->spreadsheet;
        } catch (\Exception $e) {
            Log::critical('An error occurred while creating the spreadsheet', [
                'message' => $e->getMessage(),
            ]);
            throw new SpreadSheetNotCreatedException($e->getMessage());
        }
    }

    public function setCurrency(string $currency): void {
        $this->currency = $currency;
    }

    public function setTimeZone(string $timeZone): void {
        $this->timeZone = $timeZone;
    }

    public function readable(float|string|null $amount, string $separator = ','): string {
        // Check for null or undefined amount and return '0'
        if ($amount === null || $amount === 'undefined') {
            return '0';
        }

        // Convert the amount to a string
        $amount = strval($amount);

        // If the amount is not a valid float, return it as is
        if (!is_numeric($amount) || floatval($amount) != $amount) {
            return $amount;
        }

        // Split the amount into whole and decimal parts
        $parts = explode('.', str_replace(' ', '', $amount));

        // Check if the array keys exist before accessing them
        $whole = number_format((float) $parts[0], 0, '', $separator);
        $decimal = isset($parts[1]) ? substr($parts[1].'00', 0, 2) : '';

        // Combine the whole number and decimal parts
        return $decimal !== '' && $decimal !== '0' ? "$whole.$decimal" : $whole;
    }

    public function convertUtcDateToTimezone(string|\DateTimeInterface|null $utcDate): string {
        // Create a DateTime object with the UTC-based date
        $dateTimeUtc = Carbon::parse($utcDate)->setTimezone('UTC');

        // Format the date and time as a string
        return $dateTimeUtc->format('Y-m-d H:i:s');
    }

    public function setActivatedSheet(string $sheetName): void {
        try {
            $this->worksheet = $this->spreadsheet->setActiveSheetIndexByName($sheetName);
        } catch (\Exception $e) {
            Log::critical('An error occurred while setting the active sheet survey on the spreadsheet.', [
                'message' => $e->getMessage(),
            ]);
            throw new ActiveSheetNotCreatedException($e->getMessage());
        }
    }

    public function saveSpreadSheet(): string {
        try {
            $user = User::query()->first();
            $databaseProxy = app(DatabaseProxy::class);
            $companyId = $databaseProxy->findByEmail($user->email)->getCompanyId();

            $directory = "export/{$companyId}";
            $fileName = $this->getPrefix().'-'.now()->format('Ymd_His_u').'.xlsx';
            $path = "{$directory}/{$fileName}";

            // Save spreadsheet to a temporary stream (in memory)
            $tempPath = tempnam(sys_get_temp_dir(), 'spreadsheet_');
            $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
            $writer->save($tempPath);

            Storage::put($path, file_get_contents($tempPath));
            unlink($tempPath);

            return $path;
        } catch (\Exception $e) {
            throw new SpreadSheetNotSavedException($e->getMessage());
        }
    }

    /**
     * @param array<int, string> $headers
     */
    public function saveCsv(array $headers = []): string {
        try {
            $user = User::query()->first();
            $databaseProxy = app(DatabaseProxy::class);
            $companyId = $databaseProxy->findByEmail($user->email)->getCompanyId();

            $directory = "export/{$companyId}";
            $fileName = $this->getPrefix().'-'.now()->format('Ymd_His_u').'.csv';
            $path = "{$directory}/{$fileName}";

            $csvContent = $this->generateCsvContent($headers);

            Storage::put($path, $csvContent);

            return $path;
        } catch (\Exception $e) {
            Log::critical('Error saving CSV', ['message' => $e->getMessage()]);
            throw new CsvNotSavedException($e->getMessage());
        }
    }

    /**
     * Generate CSV content as a string.
     *
     * @param array<int, string> $headers
     */
    private function generateCsvContent(array $headers = []): string {
        $stream = fopen('php://temp', 'r+');

        if ($this->exportingData->isEmpty()) {
            fclose($stream);

            return '';
        }

        // Write headers
        if ($headers === []) {
            fputcsv($stream, array_keys($this->exportingData->first()));
        } else {
            fputcsv($stream, $headers);
        }

        // Write rows
        foreach ($this->exportingData as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        $csvContent = stream_get_contents($stream);
        fclose($stream);

        return $csvContent ?: '';
    }

    public function createDirectoryIfNotExists(string $path): void {
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0775, true);
        }
    }

    public function exportDataToArray(): array {
        return $this->exportingData->toArray();
    }
}
