<?php

namespace MPM\Transaction\Export;

use App\Services\AbstractExportService;

class TransactionExportService extends AbstractExportService {
    private $transactionData;

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
        }

        foreach ($this->worksheet->getColumnIterator() as $column) {
            $this->worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }

    public function setExportingData(): void {
        $this->exportingData = $this->transactionData->map(function ($transaction) {
            $status = $transaction->originalTransaction->status == 1 ? 'Success' : ($transaction->status == 0 ? 'Pending' : 'Failed');
            $readableAmount = $this->readable($transaction->amount);

            return [
                $status,
                $transaction->original_transaction_type,
                $transaction->device->person->name.' '.$transaction->device->person->surname,
                $transaction->message,
                $readableAmount.$this->currency,
                $transaction->type,
                $this->convertUtcDateToTimezone($transaction->created_at),
            ];
        });
    }

    public function setTransactionData($transactionData): void {
        $this->transactionData = $transactionData;
    }

    public function getTemplatePath(): string {
        return storage_path('transaction/export_transactions_template.xlsx');
    }

    public function getPrefix(): string {
        return 'TransactionExport';
    }
}
