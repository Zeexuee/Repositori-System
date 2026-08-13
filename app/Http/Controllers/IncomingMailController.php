<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomingMailRequest;
use App\Http\Requests\UpdateIncomingMailRequest;
use App\Jobs\ProcessOcrJob;
use App\Models\IncomingMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

use Illuminate\Support\Str;

class IncomingMailController extends Controller
{
    /**
     * Display a listing of incoming mails with server-side pagination.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', IncomingMail::class);

        $incomingMails = IncomingMail::latest()->paginate(15);

        return view('incoming-mails.index', compact('incomingMails'));
    }

    /**
     * Show the form for creating a new incoming mail.
     */
    public function create(): View
    {
        Gate::authorize('create', IncomingMail::class);

        $nextSequenceNumber = IncomingMail::count() + 1;

        return view('incoming-mails.create', compact('nextSequenceNumber'));
    }

    /**
     * Store a newly created incoming mail in storage.
     */
    public function store(StoreIncomingMailRequest $request): RedirectResponse
    {
        Gate::authorize('create', IncomingMail::class);

        $validated = $request->validated();

        // 1. Storage Berkas PDF Utama
        $filePath = $request->hasFile('file')
            ? $request->file('file')->store('incoming-mails', 'local')
            : null;

        // 2. Storage Foto Dokumen (dari kamera/perangkat)
        $documentPhotoPath = $request->hasFile('document_photo')
            ? $request->file('document_photo')->store('incoming-mails/photos', 'local')
            : null;

        // 3. Storage Tanda Terima (Canvas Base64 atau File Gambar)
        $receiptSignaturePath = null;
        if ($request->hasFile('receipt_signature_file')) {
            $receiptSignaturePath = $request->file('receipt_signature_file')->store('incoming-mails/signatures', 'local');
        } elseif ($request->filled('receipt_signature') && str_contains((string) $request->receipt_signature, 'data:image')) {
            $sigData = explode(',', (string) $request->receipt_signature)[1] ?? '';
            if (! empty($sigData)) {
                $decoded = base64_decode($sigData);
                $sigFileName = 'incoming-mails/signatures/' . Str::uuid() . '.png';
                Storage::disk('local')->put($sigFileName, $decoded);
                $receiptSignaturePath = $sigFileName;
            }
        }

        $incomingMail = IncomingMail::create(array_merge(
            $validated,
            [
                'file_path' => $filePath,
                'document_photo_path' => $documentPhotoPath,
                'receipt_signature_path' => $receiptSignaturePath,
                'status' => $request->input('status', 'RECEIVED'),
            ]
        ));

        ProcessOcrJob::dispatch($incomingMail);

        return redirect()
            ->route('incoming-mails.index')
            ->with('success', 'Surat Masuk berhasil dicatat.');
    }

    /**
     * Display the specified incoming mail.
     */
    public function show(IncomingMail $incomingMail): View
    {
        Gate::authorize('view', $incomingMail);

        $incomingMail->load('dispositions.sender', 'dispositions.receiver');
        $users = \App\Models\User::orderBy('name')->get();

        return view('incoming-mails.show', compact('incomingMail', 'users'));
    }

    /**
     * Show the form for editing the specified incoming mail.
     */
    public function edit(IncomingMail $incomingMail): View
    {
        Gate::authorize('update', $incomingMail);

        return view('incoming-mails.edit', compact('incomingMail'));
    }

    /**
     * Update the specified incoming mail in storage.
     */
    public function update(UpdateIncomingMailRequest $request, IncomingMail $incomingMail): RedirectResponse
    {
        Gate::authorize('update', $incomingMail);

        $data = $request->validated();

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('incoming-mails', 'local');
        }

        if ($request->hasFile('document_photo')) {
            $data['document_photo_path'] = $request->file('document_photo')->store('incoming-mails/photos', 'local');
        }

        if ($request->hasFile('receipt_signature_file')) {
            $data['receipt_signature_path'] = $request->file('receipt_signature_file')->store('incoming-mails/signatures', 'local');
        } elseif ($request->filled('receipt_signature') && str_contains((string) $request->receipt_signature, 'data:image')) {
            $sigData = explode(',', (string) $request->receipt_signature)[1] ?? '';
            if (! empty($sigData)) {
                $decoded = base64_decode($sigData);
                $sigFileName = 'incoming-mails/signatures/' . Str::uuid() . '.png';
                Storage::disk('local')->put($sigFileName, $decoded);
                $data['receipt_signature_path'] = $sigFileName;
            }
        }

        $incomingMail->update($data);

        return redirect()
            ->route('incoming-mails.index')
            ->with('success', 'Surat Masuk berhasil diperbarui.');
    }

    /**
     * Remove the specified incoming mail from storage (Soft Delete).
     */
    public function destroy(IncomingMail $incomingMail): RedirectResponse
    {
        Gate::authorize('delete', $incomingMail);

        $incomingMail->delete();

        return redirect()
            ->route('incoming-mails.index')
            ->with('success', 'Surat Masuk berhasil dihapus.');
    }
}

