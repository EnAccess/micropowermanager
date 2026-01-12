<?php

namespace App\Services\ExportServices;

use App\Models\Transaction\Transaction;
use Illuminate\Support\Collection;

class TransactionExportService extends AbstractExportService {
    /**
     * @var Collection<int, Transaction>
     */
    private Collection $transactionData;

    public function writeTransactionData(): void {
        $this->setActivatedSheet('Sheet1');

        foreach ($this->exportingData as $key => $value) {
            $this->worksheet->setCellValue('A'.($key + 2), $value[0]);
            $this->worksheet->setCellValue('B'.($key + 2), $value[1]);
            $this->worksheet->setCellValue('C'.($key + 2), $value[2]);
            $this->worksheet->setCellValue('D'.($key + 2), $value[3]);
            $this->worksheet->setCellValue('E'.($key + 2), $value[4]);
            $this->worksheet->setCellValue('F'.($key + 2), $value[5]);
            $this->worksheet->setCellValue('G'.($key + 2), $value[6]);
            $this->worksheet->setCellValue('H'.($key + 2), $value[7]);
        }

        foreach ($this->worksheet->getColumnIterator() as $column) {
            $this->worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }

    public function setExportingData(): void {
        $this->exportingData = $this->transactionData->map(function (Transaction $transaction): array {
            $status = $transaction->originalTransaction->status == 1 ? 'Success' : ($transaction->originalTransaction->status == 0 ? 'Pending' : 'Failed');
            $readableAmount = $this->readable($transaction->amount);

            return [
                $status,
                $transaction->original_transaction_type,
                $transaction->device->person->name.' '.$transaction->device->person->surname,
                $transaction->message,
                $transaction->device->device_type,
                $readableAmount.$this->currency,
                $transaction->type,
                $this->convertUtcDateToTimezone($transaction->created_at),
            ];
        });
    }

    /**
     * @param Collection<int, Transaction> $transactionData
     */
    public function setTransactionData(Collection $transactionData): void {
        $this->transactionData = $transactionData;
    }

    public function getTemplatePath(): string {
        return resource_path('templates/export_transactions_template.xlsx');
    }

    public function getPrefix(): string {
        return 'TransactionExport';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportDataToArray(): array {
        if ($this->transactionData->isEmpty()) {
            return [];
        }
        // TODO: support some form of pagination to limit the data to be exported using json
        // transform exporting data to JSON structure for transaction export
        $jsonDataTransform = $this->transactionData->map(function (Transaction $transaction): array {
            return [
                'status' => $transaction->originalTransaction->status == 1 ? 'Success' : ($transaction->originalTransaction->status == 0 ? 'Pending' : 'Failed'),
                'transaction_type' => $transaction->original_transaction_type,
                'customer' => $transaction->device->person->name.' '.$transaction->device->person->surname,
                'device_id' => $transaction->device->device_serial,
                'device_type' => $transaction->device->device_type,
                'currency' => $this->currency,
                'amount' => $this->readable($transaction->amount).$this->currency,
                'sent_date' => $this->convertUtcDateToTimezone($transaction->created_at),
            ];
        });

        return $jsonDataTransform->toArray();
    }
}
