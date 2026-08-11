<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomingMailRequest;
use App\Http\Requests\UpdateIncomingMailRequest;
use App\Models\IncomingMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class IncomingMailController extends Controller
{
    /**
     * Display a listing of incoming mails with server-side pagination.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', IncomingMail::class);

        $incomingMails = IncomingMail::latest()->paginate(15);

        return view('incoming_mails.index', compact('incomingMails'));
    }

    /**
     * Show the form for creating a new incoming mail.
     */
    public function create(): View
    {
        Gate::authorize('create', IncomingMail::class);

        return view('incoming_mails.create');
    }

    /**
     * Store a newly created incoming mail in storage.
     */
    public function store(StoreIncomingMailRequest $request): RedirectResponse
    {
        Gate::authorize('create', IncomingMail::class);

        $filePath = $request->hasFile('file')
            ? $request->file('file')->store('incoming-mails', 's3')
            : null;

        IncomingMail::create(array_merge(
            $request->validated(),
            [
                'file_path' => $filePath,
                'status' => 'RECEIVED',
            ]
        ));

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

        return view('incoming_mails.show', compact('incomingMail'));
    }

    /**
     * Show the form for editing the specified incoming mail.
     */
    public function edit(IncomingMail $incomingMail): View
    {
        Gate::authorize('update', $incomingMail);

        return view('incoming_mails.edit', compact('incomingMail'));
    }

    /**
     * Update the specified incoming mail in storage.
     */
    public function update(UpdateIncomingMailRequest $request, IncomingMail $incomingMail): RedirectResponse
    {
        Gate::authorize('update', $incomingMail);

        $data = $request->validated();

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('incoming-mails', 's3');
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
