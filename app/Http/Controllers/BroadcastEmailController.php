<?php

namespace App\Http\Controllers;

use App\Mail\BroadcastMail;
use App\Models\BroadcastEmail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class BroadcastEmailController extends Controller
{
    /**
     * Display broadcast email compose form.
     */
    public function index(): View
    {
        if (! auth()->user()->hasRole('Direksi')) {
            abort(403, 'Akses Broadcast Email terbatas hanya untuk Direksi.');
        }

        $broadcasts = BroadcastEmail::with('sender')->latest()->paginate(10);
        $totalUsers = User::count();
        $stafCount = User::whereHas('roles', function ($q) {
            $q->whereRaw('LOWER(name) = ?', ['staf']);
        })->count();

        $direksiCount = User::whereHas('roles', function ($q) {
            $q->whereRaw('LOWER(name) = ?', ['direksi']);
        })->count();

        $allUserEmails = implode(', ', User::pluck('email')->filter()->toArray());
        $stafUserEmails = implode(', ', User::whereHas('roles', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['staf']))->pluck('email')->filter()->toArray());
        $direksiUserEmails = implode(', ', User::whereHas('roles', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['direksi']))->pluck('email')->filter()->toArray());

        return view('broadcast-emails.index', compact(
            'broadcasts',
            'totalUsers',
            'stafCount',
            'direksiCount',
            'allUserEmails',
            'stafUserEmails',
            'direksiUserEmails'
        ));
    }

    /**
     * Display sent broadcast emails history.
     */
    public function history(): View
    {
        if (! auth()->user()->hasRole('Direksi')) {
            abort(403, 'Akses Broadcast Email terbatas hanya untuk Direksi.');
        }

        $broadcasts = BroadcastEmail::with('sender')->latest()->paginate(15);

        return view('broadcast-emails.history', compact('broadcasts'));
    }

    /**
     * Send broadcast email to selected target audience.
     */
    public function send(Request $request): RedirectResponse
    {
        if (! auth()->user()->hasRole('Direksi')) {
            abort(403, 'Akses Broadcast Email terbatas hanya untuk Direksi.');
        }

        $validated = $request->validate([
            'recipients' => ['required', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:5'],
            'target_audience' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:15360'], // Max 15MB per attachment file
        ]);

        $rawInput = $validated['recipients'];
        $tokens = preg_split('/[\s,;]+/', $rawInput);
        $recipients = [];

        foreach ($tokens as $token) {
            $cleanEmail = trim($token);
            if (! empty($cleanEmail) && filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = strtolower($cleanEmail);
            }
        }

        $recipients = array_values(array_unique($recipients));
        $recipientCount = count($recipients);

        if ($recipientCount === 0) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Tidak ada alamat email valid yang ditemukan pada kolom Kepada (To).');
        }

        $targetAudience = $validated['target_audience'] ?: 'Custom Recipient';

        // Record broadcast entry in database first
        $broadcast = BroadcastEmail::create([
            'user_id' => auth()->id(),
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'target_audience' => $targetAudience,
            'recipient_count' => $recipientCount,
            'attachments' => [],
        ]);

        // Process file attachments and store permanently
        $mailAttachmentFiles = [];
        $attachmentMetadata = [];

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $originalName = $file->getClientOriginalName();
                    $mimeType = $file->getClientMimeType();
                    $fileSize = $file->getSize();
                    $storedPath = $file->store('broadcast_attachments/' . $broadcast->id, 'public');

                    $fullStoragePath = storage_path('app/public/' . $storedPath);
                    if (! file_exists($fullStoragePath)) {
                        $fullStoragePath = storage_path('app/' . $storedPath);
                    }

                    $mailAttachmentFiles[] = [
                        'path' => $fullStoragePath,
                        'name' => $originalName,
                        'mime' => $mimeType,
                    ];

                    $attachmentMetadata[] = [
                        'name' => $originalName,
                        'path' => $storedPath,
                        'size' => $fileSize,
                        'mime' => $mimeType,
                    ];
                }
            }
        }

        // Update broadcast record with attachment metadata
        $broadcast->update(['attachments' => $attachmentMetadata]);

        try {
            // Send individual email to each recipient to ensure 1-to-1 delivery & avoid spam filters
            foreach ($recipients as $recipient) {
                Mail::to($recipient)->send(
                    new BroadcastMail(
                        $validated['subject'],
                        $validated['body'],
                        auth()->user()->name,
                        $mailAttachmentFiles
                    )
                );
            }
        } catch (\Throwable $e) {
            Log::error('Broadcast email error: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal mengirim email: ' . $e->getMessage());
        }

        $attachmentCountInfo = count($attachmentMetadata) > 0 ? " dengan " . count($attachmentMetadata) . " lampiran dokumen" : "";

        return redirect()
            ->route('broadcast-emails.index')
            ->with('success', "Pesan broadcast email '{$validated['subject']}' berhasil terkirim ke {$recipientCount} alamat email{$attachmentCountInfo}.");
    }

    /**
     * Download an attachment from a broadcast email.
     */
    public function downloadAttachment(BroadcastEmail $broadcast, int $index)
    {
        if (! auth()->user()->hasRole('Direksi')) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $attachments = $broadcast->attachments ?? [];
        if (! isset($attachments[$index])) {
            abort(404, 'Lampiran tidak ditemukan.');
        }

        $fileInfo = $attachments[$index];
        $relativePath = $fileInfo['path'] ?? '';

        $fullPath = storage_path('app/public/' . $relativePath);
        if (! file_exists($fullPath)) {
            $fullPath = storage_path('app/' . $relativePath);
        }
        if (! file_exists($fullPath)) {
            $fullPath = storage_path('app/private/' . $relativePath);
        }

        if (! file_exists($fullPath)) {
            abort(404, 'File lampiran tidak ditemukan pada server.');
        }

        return response()->download($fullPath, $fileInfo['name'] ?? basename($fullPath), [
            'Content-Type' => $fileInfo['mime'] ?? 'application/octet-stream',
        ]);
    }
}
