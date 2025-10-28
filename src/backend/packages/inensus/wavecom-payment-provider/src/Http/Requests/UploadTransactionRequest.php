<?php

declare(strict_types=1);

namespace Inensus\WavecomPaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class UploadTransactionRequest extends FormRequest {
    public const TRANSACTION_FILE = 'transaction_file';

    public function getFile(): UploadedFile {
        return $this->file(self::TRANSACTION_FILE);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array {
        return [self::TRANSACTION_FILE => ['required', 'mimes:csv,txt']];
    }
}
