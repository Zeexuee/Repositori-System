<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveIncomingMailDispositionRequest extends FormRequest
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
            'disposition_note' => ['required', 'string', 'max:2000'],
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
            'disposition_note.required' => 'Catatan disposisi wajib diisi.',
            'recipients.required' => 'Tambahkan minimal satu penerima disposisi.',
            'recipients.*.recipient_name.required' => 'Nama penerima / unit disposisi wajib diisi.',
            'recipients.*.signature.max' => 'Ukuran tanda tangan terlalu besar.',
        ];
    }
}
