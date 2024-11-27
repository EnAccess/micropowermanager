<?php

namespace App\Services;

use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\Storage;

class PdfService {
    private PDF $pdf;

    public function __construct(PDF $pdf) {
        $this->pdf = $pdf;
    }

    public function generatePdfFromView(string $view, $dataToInject): string {
        $this->pdf->loadView($view, ['data' => $dataToInject]);

        $filePath = Storage::path('non-paying').time().'.pdf';

        $this->pdf->save($filePath);

        return $filePath;
    }
}
