<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveIncomingMailReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'mail_ids' => ['required', 'array', 'min:1', 'max:50'],
            'mail_ids.*' => ['required', 'uuid', 'distinct', 'exists:incoming_mails,id'],
            // Catatan disposisi ikut dikumpulkan di alur pengembalian dan bersifat opsional.
            'disposition_note' => ['nullable', 'string', 'max:2000'],
            'action' => ['required', 'string', 'in:save_later,complete,skip_remaining'],
            'recipients' => ['required', 'array', 'min:1', 'max:20'],
            'recipients.*.id' => ['nullable', 'uuid', 'distinct'],
            'recipients.*.recipient_name' => ['required', 'string', 'max:255'],
            'recipients.*.signer_name' => ['nullable', 'string', 'max:255'],
            'recipients.*.signature' => ['nullable', 'string', 'max:3000000'],
            'recipients.*.remove_signature' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mail_ids.required' => 'Pilih minimal satu dokumen.',
            'disposition_note.max' => 'Catatan disposisi maksimal 2000 karakter.',
            'recipients.required' => 'Tambahkan minimal satu penerima.',
            'recipients.*.recipient_name.required' => 'Nama penerima wajib diisi.',
            'recipients.*.signature.max' => 'Ukuran tanda tangan terlalu besar.',
        ];
    }
}
