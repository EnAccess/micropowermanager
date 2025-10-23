<?php

namespace App\Services;

use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\Storage;

class PdfService {
    public function __construct(private PDF $pdf) {}

    public function generatePdfFromView(string $view, mixed $dataToInject): string {
        $pdf = $this->pdf->loadView($view, ['data' => $dataToInject]);
        $pdfContent = $pdf->output();

        // Build a dynamic file path
        $timestamp = time();
        $filePath = "non-paying/{$timestamp}.pdf";

        Storage::put($filePath, $pdfContent);

        return $filePath;
    }
}
