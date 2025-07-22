<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomerGroup\CustomerGroupNotFound;
use App\Models\City;
use App\Models\ConnectionGroup;
use App\Models\ConnectionType;
use App\Models\DatabaseProxy;
use App\Models\Meter\Meter;
use App\Models\PaymentHistory;
use App\Models\Report;
use App\Models\Target;
use App\Models\Transaction\Transaction;
use App\Models\User;
use Generator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Class Reports.
 *
 * @group Export
 */
class Reports {
    private array $totalSold = [];
    private array $connectionTypeCells = [];
    private string|int $lastIndex;
    private array $subConnectionRows;

    public function __construct(
        private Spreadsheet $spreadsheet,
        private Transaction $transaction,
        private ConnectionType $connectionType,
        private ConnectionGroup $connectionGroup,
        private PaymentHistory $paymentHistory,
        private City $city,
        private Target $target,
        private Report $report,
    ) {}

    private function monthlyTargetRibbon(Worksheet $sheet): void {
        $sheet->setCellValue('a5', 'Category');
        $sheet->mergeCells('c5:e5');
        $sheet->setCellValue('c5', 'No. of CUSTOMERS connected');
        $sheet->setCellValue('f5', 'No. of contract signed but not connected yet');
        $sheet->setCellValue('g5', 'Customer in Testing phase (not paying)');
        $sheet->mergeCells('h5:j5');
        $sheet->setCellValue('h5', 'Connected POWER (kW)');
        $sheet->mergeCells('k5:o5');
        $sheet->setCellValue('k5', 'ENERGY USE (kWh)');
        $sheet->mergeCells('p5:v5');
        $sheet->setCellValue('p5', 'REVENUES (Energy Sales + Access Rate) ');

        $sheet->mergeCells('c6:e6');
        $sheet->setCellValue('c6', 'No of Customers');
        $sheet->mergeCells('h6:j6');
        $sheet->setCellValue('h6', 'Connected Power');
        $sheet->mergeCells('k6:l6');
        $sheet->setCellValue('k6', 'Energy per week');
        $sheet->mergeCells('m6:o6');
        $sheet->setCellValue('m6', 'Energy per month');
        $sheet->mergeCells('p6:q6');
        $sheet->setCellValue('p6', 'DA (in month 7, 100% implemenatation)');
        $sheet->setCellValue('r6', 'DA Updated');

        $sheet->mergeCells('s6:t6');
        $sheet->setCellValue('s6', 'Revenues per month');
        $sheet->mergeCells('u6:v6');
        $sheet->setCellValue('u6', 'Average Revenue per Customer per month');
        $sheet->setCellValue('w6', '% of average achieved target  per month');

        $sheet->setCellValue('c7', 'Target');
        $sheet->setCellValue('e7', 'Actual');
        $sheet->setCellValue('f7', 'Actual');
        $sheet->setCellValue('h7', 'Target');
        $sheet->setCellValue('j7', 'Actual');
        $sheet->setCellValue('k7', 'Target');
        $sheet->setCellValue('m7', 'Target');
        $sheet->setCellValue('o7', 'Actual');
        $sheet->setCellValue('p7', 'Per Month');
        $sheet->setCellValue('q7', 'Per Week');
        $sheet->setCellValue('s7', 'Target');
        $sheet->setCellValue('t7', 'Actual');
        $sheet->setCellValue('u7', 'Target');
        $sheet->setCellValue('v7', 'Actual');

        $this->styleSheet($sheet, 'A5:'.$sheet->getHighestDataColumn().'5', Border::BORDER_THIN, null);
        foreach ($this->excelColumnRange('A', $sheet->getHighestColumn()) as $col) {
            if ($col === 'B' || $col === 'C' || $col === 'D') {
                continue;
            }
            $sheet
                ->getColumnDimension($col)
                ->setAutoSize(true);
        }
    }

    private function addTargetConnectionGroups(Worksheet $sheet): void {
        $column = 'A';
        $subColumn = 'B';
        $row = 7;
        $connections = $this->connectionType::with(
            'meters.connectionGroup'
        )->get();
        foreach ($connections as $connection) {
            $sheet->setCellValue($column.$row, $connection->name);
            ++$row;
            foreach ($connection->meters as $meter) {
                $name = $meter->connectionGroup()->first()->name;
                $sheet->setCellValue($subColumn.$row, $name);
                $this->subConnectionRows[$name] = $row;
                ++$row;
            }
        }

        $this->monthlyTargetRibbon($sheet);
    }

    /**
     * Re-create the spreadsheet.
     */
    private function initSheet(): void {
        $this->spreadsheet = new Spreadsheet();
        $this->totalSold = [];
    }

    public function generateWithJob($startDate, $endDate, $reportType): void {
        try {
            $cities = $this->city->newQuery()->get();
            foreach ($cities as $city) {
                $this->getCustomerGroupCountPerMonth($endDate);
                $this->getCustomerGroupEnergyUsagePerMonth([$startDate, $endDate]);
                $this->generateReportForCity($city->id, $city->name, $startDate, $endDate, $reportType);
            }
        } catch (\Exception $e) {
            Log::critical(
                $reportType.' report job failed.',
                ['Exception' => $e]
            );
        }
    }

    /**
     * @param Worksheet $sheet
     * @param string    $coordinate
     * @param string    $color
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function fillBackground(Worksheet $sheet, string $coordinate, string $color): void {
        $sheet->getStyle($coordinate)->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color($color));
    }

    /**
     * @param Worksheet   $sheet
     * @param             $column
     * @param string|null $border
     * @param string|null $color
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function styleSheet(Worksheet $sheet, string $column, ?string $border, ?string $color): void {
        $style = $sheet->getStyle($column);

        if ($border !== null) {
            $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }
        if ($color !== null) {
            $style->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color($color));
        }
    }

    /**
     * @param Worksheet $sheet
     * @param           $dateRange
     *
     * @return void
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function addStaticText(Worksheet $sheet, string $dateRange): void {
        $this->styleSheet($sheet, 'A1:L4', Border::BORDER_THIN, null);
        $this->fillBackground($sheet, 'A1:A5', 'FFFABF8F');
        $this->fillBackground($sheet, 'A3:L3', 'FFFABF8F');

        $sheet->mergeCells('A1:E1');
        $this->fillBackground($sheet, 'A1:L1', 'FFFABF8F');
        $sheet->setCellValue('F1', $dateRange);
        $sheet->mergeCells('E2:F2');
        $this->fillBackground($sheet, 'E2', 'FFFABF8F');

        $this->fillBackground($sheet, 'F1', Color::COLOR_RED);

        $sheet->setCellValue('A2', 'First Receipt Nr');
        $sheet->setCellValue('A3', 'Nr. Receipt');
        $sheet->setCellValue('B3', 'Date dd/mm/yy');
        $sheet->setCellValue('C3', 'EFD Receipt Date');
        $sheet->setCellValue('D3', 'EFD Receipt Number');
        $sheet->setCellValue('E2', 'Report Balance from previous period');
        $sheet->setCellValue('E3', 'Description');
        $sheet->setCellValue('F3', 'In');
        $sheet->setCellValue('G3', 'Out');
        $sheet->setCellValue('I3', 'Customer');
        $sheet->setCellValue('J3', 'Comments');
        $sheet->setCellValue('L3', 'Date dd/m');
        $sheet->setCellValue('E5', 'Unit Sales');
        $sheet->setCellValue('E6', 'Meter');
        $sheet->setCellValue('F6', 'Amount-Made in a week');
        $sheet->setCellValue('I6', 'Customer name');
        $sheet->setCellValue('J6', 'Connection Type');

        $sheet->getRowDimension(6)->setRowHeight(30);
        $this->fillBackground($sheet, 'A6:'.$sheet->getHighestDataColumn().'6', 'FFFABF8F');

        // balance
        $sheet->mergeCells('K2:K3');
        $sheet->setCellValue('K2', 'Balance');
        $this->fillBackground($sheet, 'K2', 'FFFABF8F');
        $this->fillBackground($sheet, 'I2:J2', 'FFF000000');

        // blank line
        $sheet->mergeCells('A4:L4');
        $this->fillBackground($sheet, 'A4:L4', 'FFFABF8F');
    }

    /**
     * @param Worksheet  $sheet
     * @param string     $dateRange
     * @param Collection $transactions
     *
     * @throws CustomerGroupNotFound
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generateXls(
        Worksheet $sheet,
        string $dateRange,
        $transactions,
    ): void {
        $this->addStaticText($sheet, $dateRange);

        // Add transactions, customer name, balances to the sheet
        $this->addTransactions($sheet, $transactions);

        // add total sold summary
        $this->addSoldSummary($sheet);

        foreach ($this->excelColumnRange('A', $sheet->getHighestColumn()) as $col) {
            $sheet
                ->getColumnDimension($col)
                ->setAutoSize(true);
        }

        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(30);
    }

    /**
     * @param Worksheet                    $sheet
     * @param bool                         $addPurchaseBreakDown
     * @param Collection<int, Transaction> $transactions
     *
     * @throws CustomerGroupNotFound
     */
    private function addTransactions(Worksheet $sheet, $transactions, $addPurchaseBreakDown = true): void {
        $sheetIndex = 0;
        $balance = 0;

        foreach ($transactions as $index => $transaction) {
            if ($transaction->device->device === null) {
                continue;
            }

            $sheetIndex = $index + 7;
            $balance += $transaction->amount;

            $sheet->setCellValue('A'.$sheetIndex, $index + 1);
            $sheet->setCellValue('E'.$sheetIndex, $transaction->message);
            $sheet->setCellValue('F'.$sheetIndex, $transaction->amount);

            if (\count($transaction->paymentHistories)) {
                $paymentHistory = $transaction->paymentHistories[0];
                if (isset($paymentHistory->payer->name) && isset($paymentHistory->payer->surname)) {
                    $sheet->setCellValue(
                        'I'.$sheetIndex,
                        $paymentHistory->payer->name.' '.
                        $paymentHistory->payer->surname
                    );
                }
            }
            $sheet->setCellValue('K'.$sheetIndex, $balance);

            if ($transaction->device->device->device_type === Meter::RELATION_NAME) {
                $tariff = null;
                $connectionType = null;
                $connectionGroupName = null;

                $deviceModel = $transaction->device->device;
                if (method_exists($deviceModel, 'tariff')) {
                    $tariff = $deviceModel->tariff()->first();
                }

                if (method_exists($deviceModel, 'connectionType')) {
                    $connectionType = $deviceModel->connectionType()->first();
                }
                $sheet->setCellValue(
                    'J'.$sheetIndex,
                    $tariff->name.'-'.
                    $connectionType->name
                );

                if (method_exists($deviceModel, 'connectionGroup')) {
                    $connectionGroupName = $deviceModel->connectionGroup()->first()->name;
                }

                $paymentHistories = $this->paymentHistory
                    ->selectRaw('id, sum(amount) as amount, payment_type ')
                    ->whereIn('transaction_id', explode(',', $transaction->getAttribute('transaction_ids')))
                    ->groupBy('payment_type')
                    ->get();

                if ($addPurchaseBreakDown) {
                    $this->purchaseBreakDown(
                        $sheet,
                        $paymentHistories,
                        $sheetIndex,
                        $connectionGroupName,
                        $tariff
                    );
                }
            }
        }
        $this->lastIndex = $sheetIndex;
    }

    /**
     * Add the breakdown of the transaction amount into the right place on the spreadsheet.
     *
     * @param Worksheet $sheet
     * @param           $paymentHistories
     * @param int       $index
     * @param string    $connectionGroupName
     * @param           $tariff
     *
     * @throws CustomerGroupNotFound
     */
    private function purchaseBreakDown(
        Worksheet $sheet,
        $paymentHistories,
        int $index,
        string $connectionGroupName,
        $tariff,
    ): void {
        $column = $this->getConnectionGroupColumn($connectionGroupName);
        $soldAmount = [];
        $unit = 0;
        foreach ($paymentHistories as $paymentHistory) {
            $sheet->setCellValue($column.$index, $paymentHistory->amount);

            if ($paymentHistory->payment_type === 'access_rate' || $paymentHistory->payment_type === 'access rate') {
                $nextCol = $column;
                $sheet->setCellValue(++$nextCol.$index, $paymentHistory->amount);
                $soldAmount['access_rate'] = $paymentHistory->amount;
            } else {
                $soldAmount['energy'] = $paymentHistory->amount;
                if ($tariff->price != 0) {
                    $unit += $paymentHistory->amount / ($tariff->price / 100);
                }
            }
        }

        $this->addSoldTotal($connectionGroupName, $soldAmount, $unit);
    }

    /**
     * @param string $connectionGroupName
     *
     * @return string
     *
     * @throws CustomerGroupNotFound
     */
    private function getConnectionGroupColumn(string $connectionGroupName): string {
        if (
            array_key_exists(
                $connectionGroupName,
                $this->connectionTypeCells
            )
        ) {
            return $this->connectionTypeCells[$connectionGroupName];
        }
        throw new CustomerGroupNotFound($connectionGroupName.' not found');
    }

    private function storeConnectionGroupColumn(string $connectionGroup, string $column): void {
        $this->connectionTypeCells[$connectionGroup] = $column;
    }

    /**
     * @param Worksheet                    $sheet
     * @param Collection|ConnectionGroup[] $connectionGroups
     * @param string                       $startingColumn
     * @param int                          $startingRow
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function addConnectionGroupsToXLS(
        Worksheet $sheet,
        $connectionGroups,
        string $startingColumn,
        int $startingRow,
    ): void {
        $tmpConnectionTypeName = null;

        foreach ($connectionGroups as $connectionGroup) {
            if (!isset($connectionGroup->name)) {
                continue;
            }
            $this->storeConnectionGroupColumn(
                $connectionGroup->name,
                $startingColumn
            );

            $sheet->setCellValue($startingColumn.$startingRow, $connectionGroup->name);

            $meters = $connectionGroup->meters()->get();

            if ($meters !== null) {
                foreach ($meters as $meter) {
                    // store column to get them later when payments are placed
                    $accessRate = $meter->tariff->accessRate()->first();
                    // merge two cells if tariff has access rate
                    if ($accessRate) {
                        if ($accessRate->amount > 0) {
                            $nextColumn = $startingColumn;
                            ++$nextColumn;
                            $sheet->mergeCells($startingColumn.$startingRow.':'.
                                $nextColumn.$startingRow);
                            ++$startingColumn;
                            break;
                        }
                    }
                }
            }

            ++$startingColumn;
        }
    }

    private function addSoldTotal(string $connectionGroupName, array $amount, $unit = null): void {
        if (!array_key_exists($connectionGroupName, $this->totalSold)) {
            $this->totalSold[$connectionGroupName] = [
                'energy' => 0,
                'access_rate' => 0,
                'unit' => 0,
            ];
        }

        if ($unit !== null) {
            $this->totalSold[$connectionGroupName]['unit'] += (float) $unit;
        }
        foreach ($amount as $type => $soldAmount) {
            $this->totalSold[$connectionGroupName][$type] += (int) $soldAmount;
        }
    }

    /**
     * @param Worksheet $sheet
     *
     * @throws CustomerGroupNotFound
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function addSoldSummary(Worksheet $sheet): void {
        // place total sold 1 row below the last transaction
        $index = $this->lastIndex + 1;
        if (\count($this->totalSold) === 0) {
            $index = 10;
        }
        $energyIndex = $index + 2;

        $lastColumn = $sheet->getHighestColumn();

        $this->styleSheet(
            $sheet,
            'K5:K'.$sheet->getHighestRow(),
            null,
            'FFFABF8F'
        );

        $this->styleSheet(
            $sheet,
            'A'.$energyIndex.':'.$lastColumn.$energyIndex,
            null,
            'ffaee571'
        );

        $sheet->setCellValue('K'.$energyIndex, 'Purchased');
        $sheet->mergeCells('K'.$energyIndex.':L'.$energyIndex);

        foreach ($this->totalSold as $connectionName => $connectionData) {
            $column = $this->getConnectionGroupColumn($connectionName);
            $sheet->setCellValue($column.$index, $connectionData['energy']);
            $sheet->setCellValue($column.$energyIndex, $connectionData['unit']);
        }
    }

    /**
     * @return \Generator
     *
     * @psalm-return Generator<int, mixed, mixed, void>
     */
    private function excelColumnRange(string $lower, string $upper): \Generator {
        ++$upper;
        for ($i = $lower; $i !== $upper; ++$i) {
            yield $i;
        }
    }

    // holds the connection group and its data for the target
    private $monthlyTargetData = [];

    /**
     * @param int $cityId
     * @param     $cityName
     * @param     $startDate
     * @param     $endDate
     * @param     $reportType
     *
     * @throws CustomerGroupNotFound
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function generateReportForCity(
        int $cityId,
        $cityName,
        $startDate,
        string $endDate,
        $reportType,
    ): void {
        $this->initSheet();

        $dateRange = $startDate.'-'.$endDate;

        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle('graphs'.$startDate.'-'.$endDate);

        $transactions = $this->transaction::with([
            'device.device' => function ($query) {
                $query->select('id'); // Only load necessary fields for MorphTo
            },
        ])
            ->selectRaw('id,message,SUM(amount) as amount,GROUP_CONCAT(DISTINCT id SEPARATOR \',\') AS transaction_ids')
            ->whereHas(
                'device.address',
                function ($q) use ($cityId) {
                    $q->where('city_id', $cityId);
                }
            )
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    $q->where('status', 1);
                }
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(['message', 'id'])
            ->latest()
            ->get();

        // Then load only if device model has the relationship
        foreach ($transactions as $transaction) {
            $deviceModel = $transaction->device?->device;

            if ($deviceModel) {
                if (method_exists($deviceModel, 'tariff')) {
                    $deviceModel->loadMissing('tariff');
                }
                if (method_exists($deviceModel, 'connectionType')) {
                    $deviceModel->loadMissing('connectionType');
                }
            }
        }

        $connectionGroups = $this->connectionGroup::with('meters.connectionType')->get();

        $this->addConnectionGroupsToXLS($sheet, $connectionGroups, 'M', 5);

        $this->generateXls($sheet, $dateRange, $transactions);

        if ($reportType === 'weekly') {
            $sheet2 = new Worksheet();
            $sheet2 = $this->spreadsheet->addSheet($sheet2);
            $this->addStaticText($sheet2, $dateRange);
            $sheet2->setTitle($dateRange);

            // Add transactions, customer name, balances to the sheet
            $this->addTransactions($sheet2, $transactions, false);
        } elseif ($reportType === 'monthly') {
            $sheet2 = new Worksheet();
            $sheet2 = $this->spreadsheet->addSheet($sheet2);
            $sheet2->setTitle('monthly');
            // Add targets
            $this->addTargetConnectionGroups($sheet2);
            $this->addStoredTargets($sheet2, $cityId, $endDate);
            $this->addTargetsToXls($sheet2);
        }

        $writer = new Xlsx($this->spreadsheet);
        $dirPath = storage_path('./'.$reportType);
        $user = User::query()->first();
        $databaseProxy = app()->make(DatabaseProxy::class);
        $companyId = $databaseProxy->findByEmail($user->email)->getCompanyId();

        if (!file_exists($dirPath) && !mkdir($dirPath, 0774, true) && !is_dir($dirPath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirPath));
        }
        try {
            $fileName = str_slug($reportType.'-'.$cityName.'-'.$dateRange).'.xlsx';
            $writer->save(storage_path('./'.$reportType.'/'.$fileName));
            $this->report->create(
                [
                    'path' => storage_path($reportType.'/'.$fileName.'*'.$companyId),
                    'type' => $reportType,
                    'date' => $startDate.'---'.$endDate,
                    'name' => $cityName,
                ]
            );
        } catch (Exception $e) {
            echo 'error'.$e->getMessage();
        }
    }

    /**
     * Total number of customer groups until given date.
     *
     * @param string $date
     *
     * @return void
     */
    private function getCustomerGroupCountPerMonth(string $date): void {
        $connectionGroupsCount = Meter::query()
            ->selectRaw('Count(id) as total, connection_group_id')
            ->with('connectionGroup')
            ->where('created_at', '<', $date)
            ->groupBy('connection_group_id')->get();

        foreach ($connectionGroupsCount as $connectionGroupCount) {
            $this->monthlyTargetData[$connectionGroupCount->connectionGroup->name] = [
                'connection_id' => $connectionGroupCount->connectionGroup->id,
                'connections' => $connectionGroupCount->total ?? 0,
                'energy_per_month' => 0,
                'revenue' => 0,
                'average_revenue_per_customer' => 0.0,
            ];
        }
    }

    private function getCustomerGroupEnergyUsagePerMonth(array $dates): void {
        foreach ($this->monthlyTargetData as $connectionName => $targetData) {
            $customerGroupRevenue = $this->sumOfTransactions($targetData['connection_id'], $dates);
            foreach ($customerGroupRevenue as $groupRevenue) {
                $this->monthlyTargetData[$connectionName]['revenue'] += $groupRevenue['revenue'];

                $energyRevenue = $groupRevenue['total'];

                $tariffPrice = $groupRevenue['tariff_price'];

                if (!$tariffPrice) {
                    continue;
                }
                if (!$energyRevenue) {
                    continue;
                }
                $tariffPrice /= 100;
                if ($energyRevenue !== 0) {
                    $this->monthlyTargetData[$connectionName]['energy_per_month'] += $energyRevenue / $tariffPrice;
                }
                $this->monthlyTargetData[$connectionName]['average_revenue_per_customer']
                    = $this->monthlyTargetData[$connectionName]['revenue'] /
                    $this->monthlyTargetData[$connectionName]['connections'];
            }
        }
    }

    public function sumOfTransactions($connectionGroupId, array $dateRange): array {
        return Transaction::query()
            ->selectRaw('
        meters.connection_group_id,
        meters.serial_number as meter,
        SUM(transactions.amount) as revenue,
        meter_tariffs.price as tariff_price,
        IFNULL(SUM(payment_histories.amount), 0) as total
    ')
            ->join('meters', 'transactions.message', '=', 'meters.serial_number')
            ->join('meter_tariffs', 'meters.tariff_id', '=', 'meter_tariffs.id')
            ->join('payment_histories', 'transactions.id', '=', 'payment_histories.transaction_id', 'left')
            ->where('meters.connection_group_id', $connectionGroupId)
            ->whereBetween('transactions.created_at', $dateRange)
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    return $q->where('status', 1);
                }
            )
            ->groupBy('meters.id')
            ->get()->toArray();
    }

    private function addTargetsToXls(Worksheet $sheet): void {
        foreach ($this->monthlyTargetData as $subConnection => $monthlyTargetData) {
            $row = $this->subConnectionRows[$subConnection];
            if (!$row) {
                continue;
            }
            $sheet->setCellValue('E'.$row, $monthlyTargetData['connections']);
            $sheet->setCellValue('O'.$row, $monthlyTargetData['energy_per_month']);
            $sheet->setCellValue('T'.$row, $monthlyTargetData['revenue']);
            $sheet->setCellValue('V'.$row, $monthlyTargetData['average_revenue_per_customer']);
        }
    }

    private function addStoredTargets(Worksheet $sheet, int $cityId, string $endDate): void {
        $targetData = $this->target::with('subTargets.connectionType')
            ->where('target_date', '>', $endDate)
            ->where('owner_type', 'mini-grid')
            ->where('owner_id', $cityId)
            ->orderBy('target_date', 'asc')->first();

        if (!$targetData) { // no target is defined for that mini-grid
            return;
        }

        foreach ($targetData->subTargets as $subTarget) {
            if (!isset($this->subConnectionRows[$subTarget->connectionType->name])) {
                continue;
            }
            $row = $this->subConnectionRows[$subTarget->connectionType->name];
            $sheet->setCellValue('C'.$row, $subTarget->new_connections);
            $sheet->setCellValue('M'.$row, $subTarget->energy_per_month);
            $sheet->setCellValue('S'.$row, $subTarget->revenue);
            $sheet->setCellValue('U'.$row, $subTarget->average_revenue_per_month);
        }
    }
}
