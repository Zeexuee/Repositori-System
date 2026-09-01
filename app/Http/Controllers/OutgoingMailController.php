<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreOutgoingMailRequest;
use App\Http\Requests\UpdateOutgoingMailRequest;
use App\Jobs\ProcessDigitalSignatureJob;
use App\Models\OutgoingMail;
use App\Models\OutgoingMailFileHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OutgoingMailController extends Controller
{
    /**
     * Display a listing of outgoing mails with server-side pagination.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', OutgoingMail::class);

        $query = OutgoingMail::with('creator');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('mail_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%");
            });
        }

        $outgoingMails = $query->latest()->paginate(15)->withQueryString();

        $waitingCount = OutgoingMail::where('status', 'WAITING')->count();

        return view('outgoing-mails.index', compact('outgoingMails', 'waitingCount'));
    }

    /**
     * Display a listing of outgoing mails waiting specifically for recipient's signature.
     */
    public function waiting(Request $request): View
    {
        Gate::authorize('viewAny', OutgoingMail::class);

        $query = OutgoingMail::with('creator')->where('status', 'WAITING');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('mail_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%");
            });
        }

        $waitingMails = $query->latest()->paginate(15)->withQueryString();
        $totalWaiting = OutgoingMail::where('status', 'WAITING')->count();

        return view('outgoing-mails.waiting', compact('waitingMails', 'totalWaiting'));
    }

    /**
     * Show the form for creating a new outgoing mail.
     */
    public function create(): View
    {
        Gate::authorize('create', OutgoingMail::class);

        return view('outgoing-mails.create');
    }

    /**
     * Store a newly created outgoing mail in storage.
     */
    public function store(StoreOutgoingMailRequest $request): RedirectResponse
    {
        Gate::authorize('create', OutgoingMail::class);

        $validated = $request->validated();
        $dispositions = $this->processDispositions($request);

        if (! empty($dispositions)) {
            $validated['dispositions_data'] = $dispositions;
            $validated['recipient'] = implode(', ', array_column($dispositions, 'name'));

            $hasWaiting = false;
            foreach ($dispositions as $disp) {
                if (($disp['status'] ?? 'WAITING') === 'WAITING') {
                    $hasWaiting = true;
                    break;
                }
            }

            $status = $hasWaiting ? 'WAITING' : ($validated['status'] ?? 'RETURN');
        } else {
            $status = $validated['status'] ?? 'WAITING';
            $validated['recipient'] = $validated['recipient'] ?? '-';
        }

        $filePath = $request->hasFile('file')
            ? $request->file('file')->store('outgoing-mails', 'local')
            : null;

        $mailNumber = ! empty($validated['mail_number'])
            ? $validated['mail_number']
            : 'SK-' . now()->format('Ymd') . '-' . sprintf('%04d', OutgoingMail::whereNotNull('mail_number')->count() + 1);

        unset($validated['recipients'], $validated['dispositions']);

        $mail = OutgoingMail::create(array_merge(
            $validated,
            [
                'mail_number' => $mailNumber,
                'file_path' => $filePath,
                'created_by' => auth()->id(),
                'status' => $status,
            ]
        ));

        if ($status === 'WAITING') {
            return redirect()
                ->route('outgoing-mails.waiting')
                ->with('success', 'Surat Keluar berhasil disimpan di ruang Waiting (Menunggu Tanda Tangan Penerima).');
        }

        return redirect()
            ->route('outgoing-mails.index')
            ->with('success', 'Surat Keluar berhasil dicatat (Status: ' . $status . ').');
    }

    /**
     * Display the specified outgoing mail.
     */
    public function show(OutgoingMail $outgoingMail): View
    {
        Gate::authorize('view', $outgoingMail);

        $outgoingMail->load(['creator', 'fileHistories.uploader']);

        return view('outgoing-mails.show', compact('outgoingMail'));
    }

    /**
     * Sign a specific disposition directly from waiting room with signature canvas.
     */
    public function signDisposition(Request $request, OutgoingMail $outgoingMail): RedirectResponse
    {
        $request->validate([
            'disposition_index' => ['required', 'integer'],
            'signature_base64' => ['required', 'string'],
        ]);

        $index = (int) $request->input('disposition_index');
        $dispositions = $outgoingMail->dispositions_data ?? [];

        if (empty($dispositions) && ! empty($outgoingMail->recipient)) {
            $names = array_filter(array_map('trim', explode(',', (string) $outgoingMail->recipient)));
            foreach ($names as $name) {
                $dispositions[] = [
                    'name' => $name,
                    'status' => 'WAITING',
                    'signature_path' => null,
                    'signed_at' => null,
                ];
            }
        }

        if (! isset($dispositions[$index])) {
            return back()->with('error', 'Unit disposisi tidak ditemukan.');
        }

        $sigPath = $this->saveBase64Signature($request->input('signature_base64'));
        if (! $sigPath) {
            return back()->with('error', 'Gagal memproses gambar tanda tangan.');
        }

        $dispositions[$index]['status'] = 'SIGNED';
        $dispositions[$index]['signature_path'] = $sigPath;
        $dispositions[$index]['signed_at'] = now()->toDateTimeString();

        // Check if ALL dispositions of this mail are now SIGNED
        $allSigned = true;
        foreach ($dispositions as $disp) {
            if (($disp['status'] ?? 'WAITING') !== 'SIGNED') {
                $allSigned = false;
                break;
            }
        }

        $outgoingMail->dispositions_data = $dispositions;
        if ($allSigned) {
            $outgoingMail->status = 'RETURN';
        } else {
            $outgoingMail->status = 'WAITING';
        }
        $outgoingMail->save();

        $unitName = $dispositions[$index]['name'] ?? 'Penerima';
        $msg = "Tanda tangan untuk unit '{$unitName}' berhasil disimpan.";
        if ($allSigned) {
            $msg .= ' Seluruh unit disposisi telah menandatangani, status surat berubah menjadi RETURN.';
        }

        return back()->with('success', $msg);
    }

    /**
     * Issue PSrE Digital Signature for the outgoing mail.
     */
    public function sign(OutgoingMail $outgoingMail): RedirectResponse
    {
        Gate::authorize('sign', $outgoingMail);

        $outgoingMail->update(['status' => 'APPROVED']);

        ProcessDigitalSignatureJob::dispatch($outgoingMail, auth()->user());

        $backUrl = url()->previous();
        if (str_contains($backUrl, 'waiting')) {
            return redirect()
                ->route('outgoing-mails.waiting')
                ->with('success', 'Tanda tangan digital (PSrE) berhasil diproses untuk surat ' . ($outgoingMail->mail_number ?? ''));
        }

        return redirect()
            ->route('outgoing-mails.show', $outgoingMail)
            ->with('success', 'Tanda tangan digital (PSrE) berhasil dipicu.');
    }

    /**
     * Show the form for editing the specified outgoing mail.
     */
    public function edit(OutgoingMail $outgoingMail): View
    {
        Gate::authorize('update', $outgoingMail);

        $outgoingMail->load('fileHistories.uploader');

        return view('outgoing-mails.edit', compact('outgoingMail'));
    }

    /**
     * Update the specified outgoing mail in storage.
     */
    public function update(UpdateOutgoingMailRequest $request, OutgoingMail $outgoingMail): RedirectResponse
    {
        Gate::authorize('update', $outgoingMail);

        $data = $request->validated();
        $dispositions = $this->processDispositions($request, $outgoingMail->dispositions_data ?? []);

        if (! empty($dispositions)) {
            $data['dispositions_data'] = $dispositions;
            $data['recipient'] = implode(', ', array_column($dispositions, 'name'));

            $hasWaiting = false;
            foreach ($dispositions as $disp) {
                if (($disp['status'] ?? 'WAITING') === 'WAITING') {
                    $hasWaiting = true;
                    break;
                }
            }

            if ($hasWaiting) {
                $data['status'] = 'WAITING';
            } elseif (($outgoingMail->status === 'WAITING' || empty($data['status'])) && ! $hasWaiting) {
                $data['status'] = 'RETURN';
            }
        }
        unset($data['recipients'], $data['dispositions']);

        if ($request->hasFile('file')) {
            if (! empty($outgoingMail->file_path)) {
                OutgoingMailFileHistory::create([
                    'outgoing_mail_id' => $outgoingMail->id,
                    'file_path' => $outgoingMail->file_path,
                    'file_name' => basename($outgoingMail->file_path),
                    'uploaded_by' => auth()->id(),
                ]);
            }

            $data['file_path'] = $request->file('file')->store('outgoing-mails', 'local');
        }

        if (empty($data['mail_number']) && empty($outgoingMail->mail_number)) {
            $data['mail_number'] = 'SK-' . now()->format('Ymd') . '-' . sprintf('%04d', OutgoingMail::whereNotNull('mail_number')->count() + 1);
        }

        $outgoingMail->update($data);

        $backUrl = url()->previous();
        if (str_contains($backUrl, 'waiting')) {
            return redirect()
                ->route('outgoing-mails.waiting')
                ->with('success', 'Surat Keluar berhasil diperbarui.');
        }

        return redirect()
            ->route('outgoing-mails.index')
            ->with('success', 'Surat Keluar berhasil diperbarui.');
    }

    /**
     * Remove the specified outgoing mail from storage (Soft Delete).
     */
    public function destroy(OutgoingMail $outgoingMail): RedirectResponse
    {
        Gate::authorize('delete', $outgoingMail);

        $outgoingMail->delete();

        return redirect()
            ->route('outgoing-mails.index')
            ->with('success', 'Surat Keluar berhasil dihapus.');
    }

    /**
     * Process dispositions and signature images.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function processDispositions(Request $request, ?array $existing = null): array
    {
        $dispositionsInput = $request->input('dispositions');
        $processed = [];

        if (! empty($dispositionsInput) && is_array($dispositionsInput)) {
            foreach ($dispositionsInput as $index => $item) {
                $name = trim($item['name'] ?? '');
                if (empty($name)) {
                    continue;
                }

                $status = $item['status'] ?? 'WAITING';
                $sigPath = $item['existing_signature_path'] ?? null;

                if (! empty($item['signature_base64']) && str_starts_with($item['signature_base64'], 'data:image')) {
                    $savedPath = $this->saveBase64Signature($item['signature_base64']);
                    if ($savedPath) {
                        $sigPath = $savedPath;
                        $status = 'SIGNED';
                    }
                }

                $processed[] = [
                    'name' => $name,
                    'status' => $status,
                    'signature_path' => $sigPath,
                    'signed_at' => $status === 'SIGNED' ? ($item['signed_at'] ?? now()->toDateTimeString()) : null,
                ];
            }
        } elseif (! empty($request->input('recipients')) && is_array($request->input('recipients'))) {
            foreach ($request->input('recipients') as $r) {
                $name = trim((string) $r);
                if (! empty($name)) {
                    $processed[] = [
                        'name' => $name,
                        'status' => 'WAITING',
                        'signature_path' => null,
                        'signed_at' => null,
                    ];
                }
            }
        } elseif ($request->filled('recipient')) {
            $names = array_filter(array_map('trim', explode(',', (string) $request->input('recipient'))));
            foreach ($names as $name) {
                $processed[] = [
                    'name' => $name,
                    'status' => 'WAITING',
                    'signature_path' => null,
                    'signed_at' => null,
                ];
            }
        }

        return $processed;
    }

    /**
     * Save base64 signature image to private storage.
     */
    protected function saveBase64Signature(string $base64): ?string
    {
        try {
            if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                $data = substr($base64, strpos($base64, ',') + 1);
                $type = strtolower($type[1]);
                $decoded = base64_decode($data);
                if ($decoded !== false) {
                    $fileName = 'outgoing-mails/signatures/sig_' . Str::uuid()->toString() . '.' . ($type === 'jpeg' ? 'jpg' : $type);
                    Storage::disk('local')->put($fileName, $decoded);

                    return $fileName;
                }
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return null;
    }
}
