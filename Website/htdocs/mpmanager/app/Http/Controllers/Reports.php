<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomerGroup\CustomerGroupNotFound;
use App\Http\Resources\ApiResource;
use App\Models\City;
use App\Models\ConnectionGroup;
use App\Models\ConnectionType;
use App\Models\DatabaseProxy;
use App\Models\Meter\MeterParameter;
use App\Models\PaymentHistory;
use App\Models\Report;
use App\Models\SubConnectionType;
use App\Models\Target;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\AirtelTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\VodacomTransaction;
use App\Models\User;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use function count;

/**
 * Class Reports
 *
 * @package App\Http\Controllers\Export
 *
 * @group Export
 */
class Reports
{
    /**
     * @var array holds the summary of sold energy and amount
     */
    private $totalSold = [];

    /**
     * holds the customer group column relation
     *
     * @var array
     */
    private $connectionTypeCells = [];

    /**
     * @var Spreadsheet
     */
    private $spreadsheet;
    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var SubConnectionType
     */
    private $subConnectionType;
    /**
     * @var int|string
     */
    private $lastIndex;
    /**
     * @var ConnectionGroup
     */
    private $connectionGroup;
    /**
     * @var PaymentHistory
     */
    private $paymentHistory;
    /**
     * @var City
     */
    private $city;
    /**
     * @var ConnectionType
     */
    private $connectionType;
    /**
     * @var MeterParameter
     */
    private $meterParameter;
    /**
     * @var array
     */
    private $subConnectionRows;
    /**
     * @var Target
     */
    private $target;
    /**
     * @var Report
     */
    private $report;

    public function __construct(
        Spreadsheet $spreadsheet,
        Transaction $transaction,
        SubConnectionType $subConnectionType,
        ConnectionType $connectionType,
        ConnectionGroup $connectionGroup,
        PaymentHistory $paymentHistory,
        MeterParameter $meterParameter,
        City $city,
        Target $target,
        Report $report
    ) {
        $this->subConnectionRows = [];
        $this->spreadsheet = $spreadsheet;
        $this->transaction = $transaction;
        $this->subConnectionType = $subConnectionType;
        $this->connectionGroup = $connectionGroup;
        $this->paymentHistory = $paymentHistory;
        $this->city = $city;
        $this->connectionType = $connectionType;
        $this->meterParameter = $meterParameter;
        $this->target = $target;
        $this->report = $report;
    }

    private function monthlyTargetRibbon(Worksheet $sheet): void
    {
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


        $this->styleSheet($sheet, 'A5:' . $sheet->getHighestDataColumn() . '5', Border::BORDER_THIN, null);
        foreach ($this->excelColumnRange('A', $sheet->getHighestColumn()) as $col) {
            if ($col === 'B' || $col === 'C' || $col === 'D') {
                continue;
            }
            $sheet
                ->getColumnDimension($col)
                ->setAutoSize(true);
        }
    }

    private function addTargetConnectionGroups(Worksheet $sheet): void
    {
        $column = 'A';
        $subColumn = 'B';
        $row = 7;
        $connections = $this->connectionType::with(
            [
                'subConnections.meterParameters' => function ($q) {
                    $q->groupBy('connection_group_id');
                },
            ]
        )->get();
        foreach ($connections as $connection) {
            $sheet->setCellValue($column . $row, $connection->name);
            ++$row;
            foreach ($connection->subConnections as $subConnection) {
                foreach ($subConnection->meterParameters as $meterParameter) {
                    $sheet->setCellValue($subColumn . $row, $meterParameter->connectionGroup()->first()->name);
                    $this->subConnectionRows[$meterParameter->connectionGroup()->first()->name] = $row;
                    ++$row;
                }
            }
        }

        $this->monthlyTargetRibbon($sheet);
    }

    /**
     * Re-create the spreadsheet
     */
    private function initSheet(): void
    {
        $this->spreadsheet = new Spreadsheet();
        $this->totalSold = [];
    }


    public function generate(Request $request): ApiResource
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $reportType = $request->get('report_type');
        $city_id = $request->get('city');

        $city = $this->city->find($city_id);

        $this->getCustomerGroupCountPerMonth($endDate);
        $this->getCustomerGroupEnergyUsagePerMonth([$startDate, $endDate]);

        return new ApiResource(
            $this->generateReportForCity(
                $city->id,
                $city->name,
                $startDate,
                $endDate,
                $reportType
            )
        );
    }

    public function generateWithJob($startDate, $endDate, $reportType): void
    {
        try {
            $cities = $this->city->get();
            foreach ($cities as $city) {
                $this->getCustomerGroupCountPerMonth($endDate);
                $this->getCustomerGroupEnergyUsagePerMonth([$startDate, $endDate]);
                $this->generateReportForCity($city->id, $city->name, $startDate, $endDate, $reportType);
            }
        } catch (\Exception $e) {
            Log::critical(
                $reportType . ' report job failed.',
                ['Exception' => $e]
            );
        }
    }

    /**
     * @param Worksheet $sheet
     * @param String $coordinate
     * @param string $color
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function fillBackground(Worksheet $sheet, string $coordinate, string $color): void
    {
        $sheet->getStyle($coordinate)->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color($color));
    }

    /**
     * @param Worksheet $sheet
     * @param $column
     * @param string|null $border
     * @param string|null $color
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function styleSheet(Worksheet $sheet, string $column, ?string $border, ?string $color): void
    {
        $style = $sheet->getStyle($column);

        if ($border !== null) {
            $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }
        if ($color !== null) {
            $style->getFill()->setFillType(FILL::FILL_SOLID)->setStartColor((new Color($color)));
        }
    }

    /**
     * @param Worksheet $sheet
     * @param $dateRange
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     *
     * @return void
     */
    private function addStaticText(Worksheet $sheet, string $dateRange): void
    {
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
        $this->fillBackground($sheet, 'A6:' . $sheet->getHighestDataColumn() . '6', 'FFFABF8F');

        //balance
        $sheet->mergeCells('K2:K3');
        $sheet->setCellValue('K2', 'Balance');
        $this->fillBackground($sheet, 'K2', 'FFFABF8F');
        $this->fillBackground($sheet, 'I2:J2', 'FFF000000');


        //blank line
        $sheet->mergeCells('A4:L4');
        $this->fillBackground($sheet, 'A4:L4', 'FFFABF8F');
    }

    /**
     * @param Worksheet $sheet
     * @param String $dateRange
     * @param Builder[]|Collection $transactions
     *
     * @throws CustomerGroupNotFound
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generateXls(
        Worksheet $sheet,
        string $dateRange,
        $transactions
    ): void {
        $this->addStaticText($sheet, $dateRange);

        //Add transactions, customer name, balances to the sheet
        $this->addTransactions($sheet, $transactions);


        //add total sold summary
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
     * @param Worksheet $sheet
     * @param bool $addPurchaseBreakDown
     * @param Builder[]|Collection $transactions
     *
     * @throws CustomerGroupNotFound
     */
    private function addTransactions(Worksheet $sheet, $transactions, $addPurchaseBreakDown = true): void
    {
        $sheetIndex = 0;
        $balance = 0;

        foreach ($transactions as $index => $transaction) {
            if (!($transaction->meter) || !($transaction->meter->meterParameter)) {
                continue;
            }

            $sheetIndex = $index + 7;
            $balance += $transaction->amount;

            $sheet->setCellValue('A' . $sheetIndex, $index + 1);
            $sheet->setCellValue('E' . $sheetIndex, $transaction->message);
            $sheet->setCellValue('F' . $sheetIndex, $transaction->amount);

            if (count($transaction->paymentHistories)) {
                $sheet->setCellValue(
                    'I' . $sheetIndex,
                    $transaction->paymentHistories[0]->payer->name . ' ' .
                    $transaction->paymentHistories[0]->payer->surname
                );
            }
            $sheet->setCellValue('K' . $sheetIndex, $balance);
            $sheet->setCellValue(
                'J' . $sheetIndex,
                $transaction->meter()->first()->meterParameter()->first()->tariff()->first()->name . '-' .
                $transaction->meter()->first()->meterParameter()->first()->connectionType()->first()->name
            );


            $connectionGroupName = $transaction
                ->meter()->first()
                ->meterParameter()->first()
                ->connectionGroup()->first()->name;

            $paymentHistories = $this->paymentHistory
                ->selectRaw('id, sum(amount) as amount, payment_type ')
                ->whereIn('transaction_id', explode(',', $transaction->transaction_ids))
                ->groupBy('payment_type')
                ->get();

            if ($addPurchaseBreakDown) {
                $this->purchaseBreakDown(
                    $sheet,
                    $paymentHistories,
                    $sheetIndex,
                    $connectionGroupName,
                    $transaction->meter()->first()->meterParameter()->first()->tariff()->first()
                );
            }
        }
        $this->lastIndex = $sheetIndex;
    }


    /**
     * Add the breakdown of the transaction amount into the right place on the spreadsheet
     *
     * @param Worksheet $sheet
     * @param $paymentHistories
     * @param int $index
     *
     * @param String $connectionGroupName
     *
     * @param $tariff
     *
     * @throws CustomerGroupNotFound
     */
    private function purchaseBreakDown(
        Worksheet $sheet,
        $paymentHistories,
        int $index,
        string $connectionGroupName,
        $tariff
    ): void {

        $column = $this->getConnectionGroupColumn($connectionGroupName);
        $soldAmount = [];
        $unit = 0;
        foreach ($paymentHistories as $paymentHistory) {
            $sheet->setCellValue($column . $index, $paymentHistory->amount);

            if ($paymentHistory->payment_type === 'access_rate' || $paymentHistory->payment_type === 'access rate') {
                $nextCol = $column;
                $sheet->setCellValue(++$nextCol . $index, $paymentHistory->amount);
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
     * @param String $connectionGroupName
     *
     * @return string
     *
     * @throws CustomerGroupNotFound
     */
    private function getConnectionGroupColumn(string $connectionGroupName): string
    {
        if (
            array_key_exists(
                $connectionGroupName,
                $this->connectionTypeCells
            )
        ) {
            return $this->connectionTypeCells[$connectionGroupName];
        }
        throw new CustomerGroupNotFound($connectionGroupName . ' not found');
    }

    private function storeConnectionGroupColumn(string $connectionGroup, string $column): void
    {
        $this->connectionTypeCells[$connectionGroup] = $column;
    }

    /**
     * @param Worksheet $sheet
     * @param $connectionGroups
     * @param string $startingColumn
     * @param int $startingRow
     * @param Builder[]|Collection $connectionGroups
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function addConnectionGroupsToXLS(
        Worksheet $sheet,
        $connectionGroups,
        string $startingColumn,
        int $startingRow
    ): void {
        $tmpConnectionTypeName = null;

        foreach ($connectionGroups as $connectionGroup) {
            $this->storeConnectionGroupColumn(
                $connectionGroup->name,
                $startingColumn
            );

            $sheet->setCellValue($startingColumn . $startingRow, $connectionGroup->name);

            if (($meterParameters = $connectionGroup->meterParameters()->get()) !== null) {
                foreach ($meterParameters as $meterParameter) {
                    //store column to get them later when payments are placed
                    $accessRate = $meterParameter->tariff()->first()->accessRate()->first();
                    //merge two cells if tariff has access rate
                    if ($accessRate) {
                        if ($accessRate->amount > 0) {
                            $nextColumn = $startingColumn;
                            ++$nextColumn;
                            $sheet->mergeCells($startingColumn . $startingRow . ':' .
                                $nextColumn . $startingRow);
                            ++$startingColumn;
                            break;
                        }
                    }
                }
            }


            $startingColumn++;
        }
    }

    private function addSoldTotal(string $connectionGroupName, array $amount, $unit = null): void
    {
        if (!array_key_exists($connectionGroupName, $this->totalSold)) {
            $this->totalSold[$connectionGroupName] = [
                'energy' => 0,
                'access_rate' => 0,
                'unit' => 0,
            ];
        }

        if ($unit !== null) {
            $this->totalSold[$connectionGroupName]['unit'] += (double)$unit;
        }
        foreach ($amount as $type => $soldAmount) {
            $this->totalSold[$connectionGroupName][$type] += (int)$soldAmount;
        }
    }

    /**
     * @param Worksheet $sheet
     *
     * @throws CustomerGroupNotFound
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function addSoldSummary(Worksheet $sheet): void
    {
        // place total sold 1 row below the last transaction
        $index = $this->lastIndex + 1;
        if (count($this->totalSold) === 0) {
            $index = 10;
        }
        $energyIndex = $index + 2;

        $lastColumn = $sheet->getHighestColumn();


        $this->styleSheet(
            $sheet,
            'K5:K' . $sheet->getHighestRow(),
            null,
            'FFFABF8F'
        );


        $this->styleSheet(
            $sheet,
            'A' . $energyIndex . ':' . $lastColumn . $energyIndex,
            null,
            'ffaee571'
        );

        $sheet->setCellValue('K' . $energyIndex, 'Purchased');
        $sheet->mergeCells('K' . $energyIndex . ':L' . $energyIndex);

        foreach ($this->totalSold as $connectionName => $connectionData) {
            $column = $this->getConnectionGroupColumn($connectionName);
            $sheet->setCellValue($column . $index, $connectionData['energy']);
            $sheet->setCellValue($column . $energyIndex, $connectionData['unit']);
        }
    }

    /**
     * @return Generator
     *
     * @psalm-return Generator<int, mixed, mixed, void>
     */
    private function excelColumnRange(string $lower, string $upper): Generator
    {
        ++$upper;
        for ($i = $lower; $i !== $upper; ++$i) {
            yield $i;
        }
    }


    //holds the connection group and its data for the target
    private $monthlyTargetDatas = [];

    /**
     * @param int $cityId
     * @param $cityName
     * @param $startDate
     * @param $endDate
     *
     * @param $reportType
     *
     * @throws CustomerGroupNotFound
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function generateReportForCity(
        int $cityId,
        $cityName,
        $startDate,
        string $endDate,
        $reportType
    ): void {

        $this->initSheet();

        $dateRange = $startDate . '-' . $endDate;

        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle('graphs' . $startDate . '-' . $endDate);

        $transactions = $this->transaction::with(['meter.meterParameter.tariff', 'meter.meterParameter.connectionType'])
            ->selectRaw('id,message,SUM(amount) as amount,GROUP_CONCAT(DISTINCT id SEPARATOR \',\') AS transaction_ids')
            ->whereHas(
                'meter.meterParameter.address',
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
            ->groupBy('message')
            ->latest()
            ->get();

        $connectionGroups = $this->connectionGroup::with('meterParameters.connectionType')->get();


        $this->addConnectionGroupsToXLS($sheet, $connectionGroups, 'M', 5);


        $this->generateXls($sheet, $dateRange, $transactions);

        if ($reportType === 'weekly') {
            $sheet2 = new Worksheet();
            $sheet2 = $this->spreadsheet->addSheet($sheet2);
            $this->addStaticText($sheet2, $dateRange);
            $sheet2->setTitle($dateRange);
            //Add transactions, customer name, balances to the sheet
            $this->addTransactions($sheet2, $transactions, false);
        } elseif ($reportType === 'monthly') {
            $sheet2 = new Worksheet();
            $sheet2 = $this->spreadsheet->addSheet($sheet2);
            $sheet2->setTitle('monthly');
            //Add targets
            $this->addTargetConnectionGroups($sheet2);
            $this->addStoredTargets($sheet2, $cityId, $endDate);
            $this->addTargetsToXls($sheet2);
        }


        $writer = new Xlsx($this->spreadsheet);
        $dirPath = storage_path('./' . $reportType);
        $user = User::query()->first();
        $databaseProxy = app()->make(DatabaseProxy::class);
        $companyId = $databaseProxy->findByEmail($user->email)->getCompanyId();

        if (!file_exists($dirPath) && !mkdir($dirPath, 0774, true) && !is_dir($dirPath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirPath));
        }
        try {
            $fileName = str_slug($reportType . '-' . $cityName . '-' . $dateRange) . '.xlsx';
            $writer->save(storage_path('./' . $reportType . '/' . $fileName));
            $this->report->create(
                [
                    'path' => storage_path($reportType . '/' . $fileName.'*'.$companyId),
                    'type' => $reportType,
                    'date' => $startDate . '---' . $endDate,
                    'name' => $cityName,
                ]
            );
        } catch (Exception $e) {
            echo 'error' . $e->getMessage();
        }
    }

    /**
     * Total number of customer groups until given date
     *
     * @param string $date
     *
     * @return void
     */
    private function getCustomerGroupCountPerMonth(string $date): void
    {
        $connectionGroupsCount = MeterParameter::selectRaw('Count(id) as total, connection_group_id')
            ->with('connectionGroup')
            ->where('created_at', '<', $date)
            ->groupBy('connection_group_id')->get();

        foreach ($connectionGroupsCount as $connectionGroupCount) {
            $this->monthlyTargetDatas[$connectionGroupCount->connectionGroup->name] = [
                'connection_id' => $connectionGroupCount->connectionGroup->id,
                'connections' => $connectionGroupCount->total,
                'energy_per_month' => 0,
                'revenue' => 0,
                'average_revenue_per_customer' => 0.0,
            ];
        }
    }

    private function getCustomerGroupEnergyUsagePerMonth(array $dates): void
    {

        foreach ($this->monthlyTargetDatas as $connectionName => $targetData) {
            $customerGroupRevenue = $this->sumOfTransactions($targetData['connection_id'], $dates);
            foreach ($customerGroupRevenue as $groupRevenue) {
                $this->monthlyTargetDatas[$connectionName]['revenue'] += $groupRevenue->revenue;

                $energyRevenue = $groupRevenue->total;

                $tariffPrice = $groupRevenue->tariff_price;

                if (!$tariffPrice || $tariffPrice === 0) {
                    continue;
                }
                if (!$energyRevenue || $energyRevenue === 0) {
                    continue;
                }
                $tariffPrice /= 100;
                if ($energyRevenue !== 0) {
                    $this->monthlyTargetDatas[$connectionName]['energy_per_month'] += $energyRevenue / $tariffPrice;
                }
                $this->monthlyTargetDatas[$connectionName]['average_revenue_per_customer']
                    = $this->monthlyTargetDatas[$connectionName]['revenue'] /
                    $this->monthlyTargetDatas[$connectionName]['connections'];
            }
        }
    }


    public function sumOfTransactions($connectionGroupId, array $dateRange): array
    {
        return Transaction::query()
            ->selectRaw('
        meter_parameters.connection_group_id,
        meters.serial_number as meter,
        SUM(transactions.amount) as revenue,
        meter_tariffs.price as tariff_price,
        IFNULL(SUM(payment_histories.amount), 0) as total
    ')
            ->join('meters', 'transactions.message', '=', 'meters.serial_number')
            ->join('meter_parameters', 'meters.id', '=', 'meter_parameters.meter_id')
            ->join('meter_tariffs', 'meter_parameters.tariff_id', '=', 'meter_tariffs.id')
            ->join('payment_histories', 'transactions.id', '=', 'payment_histories.transaction_id', 'left')
            ->where('meter_parameters.connection_group_id', $connectionGroupId)
            ->whereBetween('transactions.created_at', $dateRange)
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    return $q->where('status', 1);
                }
            )
            ->groupBy('meter_parameters.meter_id')
            ->get()->toArray();

    }

    private function addTargetsToXls(Worksheet $sheet): void
    {
        foreach ($this->monthlyTargetDatas as $subConnection => $monthlyTargetData) {
            $row = $this->subConnectionRows[$subConnection];
            if (!$row) {
                continue;
            }
            $sheet->setCellValue('E' . $row, $monthlyTargetData['connections']);
            $sheet->setCellValue('O' . $row, $monthlyTargetData['energy_per_month']);
            $sheet->setCellValue('T' . $row, $monthlyTargetData['revenue']);
            $sheet->setCellValue('V' . $row, $monthlyTargetData['average_revenue_per_customer']);
        }
    }

    private function addStoredTargets(Worksheet $sheet, int $cityId, $endDate): void
    {
        $targetData = $this->target::with('subTargets.connectionType')
            ->where('target_date', '>', $endDate)
            ->where('owner_type', 'mini-grid')
            ->where('owner_id', $cityId)
            ->orderBy('target_date', 'asc')->first();

        if (!$targetData) { //no target is defined for that mini-grid
            return;
        }

        foreach ($targetData->subTargets as $subTarget) {
            if (!isset($this->subConnectionRows[$subTarget->connectionType->name])) {
                continue;
            }
            $row = $this->subConnectionRows[$subTarget->connectionType->name];
            $sheet->setCellValue('C' . $row, $subTarget->new_connections);
            $sheet->setCellValue('M' . $row, $subTarget->energy_per_month);
            $sheet->setCellValue('S' . $row, $subTarget->revenue);
            $sheet->setCellValue('U' . $row, $subTarget->average_revenue_per_month);
        }
    }
}
