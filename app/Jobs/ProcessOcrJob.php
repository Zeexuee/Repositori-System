<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\IncomingMail;
use App\Services\OcrProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOcrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public IncomingMail $incomingMail
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OcrProcessingService $ocrService): void
    {
        if (empty($this->incomingMail->file_path)) {
            return;
        }

        $extractedText = $ocrService->extractText($this->incomingMail->file_path);

        // Menyimpan hasil pemrosesan OCR
    }
}
