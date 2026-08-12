<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMailDispositionRequest;
use App\Models\IncomingMail;
use App\Models\MailDisposition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MailDispositionController extends Controller
{
    /**
     * Store a newly created mail disposition in storage.
     */
    public function store(StoreMailDispositionRequest $request, IncomingMail $incomingMail): RedirectResponse
    {
        Gate::authorize('create', MailDisposition::class);

        MailDisposition::create([
            'incoming_mail_id' => $incomingMail->id,
            'sender_id' => auth()->id(),
            'receiver_id' => $request->validated('receiver_id'),
            'instruction' => $request->validated('instruction'),
            'deadline_date' => $request->validated('deadline_date'),
        ]);

        $incomingMail->update(['status' => 'DISPATCHED']);

        return redirect()
            ->route('incoming-mails.show', $incomingMail)
            ->with('success', 'Disposisi surat berhasil ditambahkan dan status surat diperbarui.');
    }
}
