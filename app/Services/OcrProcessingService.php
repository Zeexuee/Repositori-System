<?php

declare(strict_types=1);

namespace App\Services;

class OcrProcessingService
{
    /**
     * Extract raw text content from a document file using OCR engine.
     *
     * @param string $filePath Path to the document in storage/S3
     * @return string Extracted raw text content
     */
    public function extractText(string $filePath): string
    {
        // Logica awal / placeholder untuk pemrosesan OCR
        return '';
    }
}
