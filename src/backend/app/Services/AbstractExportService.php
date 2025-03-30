<?php

namespace App\Services;

use App\Exceptions\ActiveSheetNotCreatedException;
use App\Exceptions\SpreadSheetNotCreatedException;
use App\Exceptions\SpreadSheetNotSavedException;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class AbstractExportService {
    protected IReader $reader;
    protected Worksheet $worksheet;
    protected Spreadsheet $spreadsheet;
    protected Collection $exportingData;
    protected string $currency;
    protected string $timeZone;
    protected string $recentlyCreatedSpreadSheetId;

    abstract public function setExportingData();

    abstract public function getTemplatePath();

    abstract public function getPrefix();

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

    public function setCurrency($currency) {
        $this->currency = $currency;
    }

    public function setTimeZone($timeZone) {
        $this->timeZone = $timeZone;
    }

    public function readable($amount, $separator = ',') {
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
        $whole = isset($parts[0]) ? number_format($parts[0], 0, '', $separator) : '';
        $decimal = isset($parts[1]) ? substr($parts[1].'00', 0, 2) : '';

        // Combine the whole number and decimal parts
        return $decimal ? "$whole.$decimal" : $whole;
    }

    public function convertUtcDateToTimezone($utcDate): string {
        // Create a DateTime object with the UTC-based date
        $dateTimeUtc = Carbon::parse($utcDate)->setTimezone('UTC');

        // Set the desired timezone
        $dateTimeUtc->setTimezone(new \DateTimeZone($this->timeZone));

        // Format the date and time as a string
        return $dateTimeUtc->format('Y-m-d H:i:s');
    }

    public function setRecentlyCreatedSpreadSheetId(string $id): void {
        $this->recentlyCreatedSpreadSheetId = $id;
    }

    public function setActivatedSheet($sheetName): void {
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
            $uuid = Str::uuid()->toString();
            $fileName = storage_path('appliance').'/'.$this->getPrefix().'-'.$uuid.'.xlsx';
            $this->setRecentlyCreatedSpreadSheetId($uuid);
            $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
            $writer->save($fileName);

            return $fileName;
        } catch (\Exception $e) {
            throw new SpreadSheetNotSavedException($e->getMessage());
        }
    }
}
