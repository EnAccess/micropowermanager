<?php

namespace App\Services;

use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\Storage;

class PdfService {
    public function __construct(private PDF $pdf) {}

    public function generatePdfFromView(string $view, mixed $dataToInject): string {
        $this->pdf->loadView($view, ['data' => $dataToInject]);

        $filePath = Storage::path('non-paying').time().'.pdf';

        $this->pdf->save($filePath);

        return $filePath;
    }
}
