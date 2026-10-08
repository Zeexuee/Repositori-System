<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('test:revisi')]
#[Description('Test revision notes flow')]
class TestRevisi extends Command
{
    public function handle(\App\Services\IncomingMailStatusTransitionService $transitionService)
    {
        $mail = \App\Models\IncomingMail::first();
        if (!$mail) {
            $this->error('No incoming mail to test with.');
            return;
        }

        $userId = \App\Models\User::first()->id ?? null;
        $this->info("Initial status: " . $mail->status);

        // Current status could be anything, but we need it to be able to transition to REVISI
        // Let's force it to RECEIVE first so it can transition to REVISI
        if (\App\Support\IncomingMailStatus::canonical($mail->status) !== 'RECEIVE') {
            $mail->update(['status' => 'RECEIVE']);
        }

        // 1. Move to REVISI
        try {
            $transitionService->apply($mail, 'REVISI', $userId, 'Tolong perbaiki judulnya');
            $this->info("Moved to REVISI successfully.");
        } catch (\Exception $e) {
            $this->error("Failed to move to REVISI: " . $e->getMessage());
        }

        // 2. Refresh and move back to RECEIVE
        $mail->refresh();
        try {
            $transitionService->apply($mail, 'RECEIVE', $userId, 'Sudah saya perbaiki sesuai arahan');
            $this->info("Moved back to RECEIVE successfully.");
        } catch (\Exception $e) {
            $this->error("Failed to move to RECEIVE: " . $e->getMessage());
        }

        // 3. Print notes
        $mail->refresh();
        $this->info("Total Revision Notes: " . $mail->revisionNotes()->count());
        foreach ($mail->revisionNotes as $note) {
            $this->info("- Type: {$note->type} | Note: {$note->note}");
        }
    }
}
