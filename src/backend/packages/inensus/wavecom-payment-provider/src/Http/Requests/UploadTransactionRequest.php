<?php

declare(strict_types=1);

namespace Inensus\WavecomPaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadTransactionRequest extends FormRequest {
    public const TRANSACTION_FILE = 'transaction_file';

    public function getFile(): \Illuminate\Http\UploadedFile {
        return $this->file(self::TRANSACTION_FILE);
    }

    public function rules() {
        return [self::TRANSACTION_FILE => 'required|mimes:csv,txt'];
    }
}
