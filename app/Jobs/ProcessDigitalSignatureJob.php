<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\OutgoingMail;
use App\Models\User;
use App\Services\DocumentSignatureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDigitalSignatureJob implements ShouldQueue
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
        public OutgoingMail $outgoingMail,
        public User $signer
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DocumentSignatureService $signatureService): void
    {
        if (empty($this->outgoingMail->file_path)) {
            $this->outgoingMail->update(['status' => 'SIGN_FAILED']);
            return;
        }

        $success = $signatureService->signDocument($this->outgoingMail->file_path, $this->signer);

        if ($success) {
            $this->outgoingMail->update(['status' => 'SIGNED']);
        } else {
            $this->outgoingMail->update(['status' => 'SIGN_FAILED']);
        }
    }
}
