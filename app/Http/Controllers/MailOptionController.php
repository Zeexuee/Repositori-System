<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\IncomingMail;
use App\Models\OutgoingMail;
use App\Support\MailOptionList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Menyimpan penghapusan / registrasi ulang opsi riwayat combobox secara permanen.
 */
class MailOptionController extends Controller
{
    /**
     * Hapus (sembunyikan) opsi dari daftar saran.
     */
    public function destroy(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $data = $this->validatePayload($request);

        foreach ($data['values'] as $value) {
            MailOptionList::exclude($data['type'], (string) $value, $request->user()?->id);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Daftarkan kembali opsi yang sebelumnya dihapus.
     */
    public function restore(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $data = $this->validatePayload($request);

        foreach ($data['values'] as $value) {
            MailOptionList::restore($data['type'], (string) $value);
        }

        return response()->json(['success' => true]);
    }

    /**
     * @return array{type: string, values: list<string>}
     */
    private function validatePayload(Request $request): array
    {
        if ($request->filled('value') && ! $request->has('values')) {
            $request->merge(['values' => [$request->input('value')]]);
        }

        /** @var array{type: string, values: list<string>} $validated */
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(MailOptionList::TYPES)],
            'values' => ['required', 'array', 'min:1', 'max:100'],
            'values.*' => ['required', 'string', 'max:255'],
        ]);

        return $validated;
    }

    private function authorizeAccess(): void
    {
        abort_unless(
            Gate::allows('create', IncomingMail::class) || Gate::allows('create', OutgoingMail::class),
            403
        );
    }
}

