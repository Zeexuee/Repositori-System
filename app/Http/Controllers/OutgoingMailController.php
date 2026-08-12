<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreOutgoingMailRequest;
use App\Http\Requests\UpdateOutgoingMailRequest;
use App\Jobs\ProcessDigitalSignatureJob;
use App\Models\OutgoingMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OutgoingMailController extends Controller
{
    /**
     * Display a listing of outgoing mails with server-side pagination.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', OutgoingMail::class);

        $outgoingMails = OutgoingMail::with('creator')->latest()->paginate(15);

        return view('outgoing-mails.index', compact('outgoingMails'));
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

        $filePath = $request->hasFile('file')
            ? $request->file('file')->store('outgoing-mails')
            : null;

        OutgoingMail::create(array_merge(
            $request->validated(),
            [
                'file_path' => $filePath,
                'created_by' => auth()->id(),
                'status' => 'DRAFT',
            ]
        ));

        return redirect()
            ->route('outgoing-mails.index')
            ->with('success', 'Surat Keluar berhasil dibuat sebagai draf.');
    }

    /**
     * Display the specified outgoing mail.
     */
    public function show(OutgoingMail $outgoingMail): View
    {
        Gate::authorize('view', $outgoingMail);

        $outgoingMail->load('creator');

        return view('outgoing-mails.show', compact('outgoingMail'));
    }

    /**
     * Issue PSrE Digital Signature for the outgoing mail.
     */
    public function sign(OutgoingMail $outgoingMail): RedirectResponse
    {
        Gate::authorize('sign', $outgoingMail);

        $outgoingMail->update(['status' => 'READY_FOR_SIGN']);

        ProcessDigitalSignatureJob::dispatch($outgoingMail, auth()->user());

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

        return view('outgoing-mails.edit', compact('outgoingMail'));
    }

    /**
     * Update the specified outgoing mail in storage.
     */
    public function update(UpdateOutgoingMailRequest $request, OutgoingMail $outgoingMail): RedirectResponse
    {
        Gate::authorize('update', $outgoingMail);

        $data = $request->validated();

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('outgoing-mails');
        }

        $outgoingMail->update($data);

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
}

