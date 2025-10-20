<?php

namespace App\Services;

use App\Support\AppStorage;
use Barryvdh\DomPDF\PDF;

class PdfService {
    public function __construct(private PDF $pdf) {}

    public function generatePdfFromView(string $view, mixed $dataToInject): string {
        $pdf = $this->pdf->loadView($view, ['data' => $dataToInject]);
        $pdfContent = $pdf->output();

        // Build a dynamic file path
        $timestamp = time();
        $filePath = "non-paying/{$timestamp}.pdf";

        AppStorage::put($filePath, $pdfContent);

        return $filePath;
    }
}
