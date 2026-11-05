<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\MailRecipient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncomingMailRequest extends FormRequest
{
    /**
     * Normalize recipient inputs (array or comma-separated string) prior to validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('recipient')) {
            $this->merge([
                'recipient' => MailRecipient::format($this->input('recipient')),
            ]);
        }

        if (is_array($this->input('documents'))) {
            $documents = $this->input('documents');
            foreach ($documents as $index => $doc) {
                if (is_array($doc) && array_key_exists('recipient', $doc)) {
                    $documents[$index]['recipient'] = MailRecipient::format($doc['recipient']) ?? '';
                }
            }
            $this->merge(['documents' => $documents]);
        }
    }
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isDraft = filter_var($this->input('is_draft'), FILTER_VALIDATE_BOOLEAN);
        $hasDocuments = is_array($this->input('documents')) && count($this->input('documents')) > 0;

        $docRecipientRule = $isDraft ? ['nullable', 'string', 'max:2000'] : ['required', 'string', 'max:2000'];
        $docSubjectRule = $isDraft ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'];

        $topSubjectRule = ($hasDocuments || $isDraft) ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'];

        return [
            'sender' => ['required', 'string', 'max:255'],
            'received_date' => ['required', 'date'],

            'recipient' => ['nullable', 'string', 'max:2000'],
            'is_draft' => ['nullable'],
            'receipt_signature' => ['nullable', 'string'],
            'receipt_signature_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            // Single document input fields
            'mail_number' => ['nullable', 'string', 'max:255', Rule::unique('incoming_mails', 'mail_number')->whereNull('deleted_at')],
            'subject' => $topSubjectRule,
            'progress' => ['nullable', 'string', 'in:RECEIVE,RECEIVED,REVISI,DRAFT'],
            'status' => ['nullable', 'string', 'max:255'],
            'outgoing_date' => ['nullable', 'date'],
            'disposition_note' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'document_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],

            // Multi-document batch input fields
            'documents' => ['nullable', 'array'],
            'documents.*.recipient' => $docRecipientRule,
            'documents.*.subject' => $docSubjectRule,
            'documents.*.mail_number' => ['nullable', 'string', 'max:255', 'distinct:ignore_case', Rule::unique('incoming_mails', 'mail_number')->whereNull('deleted_at')],
            'documents.*.outgoing_date' => ['nullable', 'date'],
            'documents.*.disposition_note' => ['nullable', 'string'],
            'documents.*.notes' => ['nullable', 'string'],
            'documents.*.progress' => ['nullable', 'string', 'in:RECEIVE,RECEIVED,REVISI,DRAFT'],
            'documents.*.status' => ['nullable', 'string', 'max:255'],
            'documents.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'documents.*.document_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'documents.*.files' => ['nullable', 'array'],
            'documents.*.files.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sender' => 'Biro / Pengirim',
            'received_date' => 'Tanggal Masuk',

            'documents' => 'Daftar Dokumen',
            'documents.*.recipient' => 'Kepada / Penerima Dokumen',
            'documents.*.subject' => 'Perihal Dokumen',
            'documents.*.mail_number' => 'Nomor Surat',
            'documents.*.file' => 'Berkas Dokumen',
            'documents.*.document_photo' => 'Foto Dokumen',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [
            'sender.required' => 'Biro / Pengirim wajib dipilih atau diisi.',
            'received_date.required' => 'Tanggal Masuk wajib diisi.',
            'received_date.date' => 'Format Tanggal Masuk tidak valid.',
            'mail_number.unique' => 'Nomor Surat ini sudah ada di dalam sistem.',

            'documents.*.recipient.required' => 'Kepada / Penerima pada dokumen wajib diisi.',
            'documents.*.subject.required' => 'Perihal pada dokumen wajib diisi.',
            'documents.*.file.max' => 'Ukuran berkas dokumen tidak boleh lebih dari 10MB.',
            'documents.*.file.mimes' => 'Format berkas dokumen harus PDF, JPG, JPEG, PNG, atau WEBP.',
        ];

        // Berikan pesan error spesifik dengan nomor dokumen
        $documents = $this->input('documents', []);
        if (is_array($documents)) {
            foreach (array_keys($documents) as $index) {
                $docNum = (int) $index + 1;
                $messages["documents.{$index}.recipient.required"] = "Kepada / Penerima pada Dokumen #{$docNum} wajib diisi.";
                $messages["documents.{$index}.subject.required"] = "Perihal pada Dokumen #{$docNum} wajib diisi.";
                $messages["documents.{$index}.file.max"] = "Ukuran file pada Dokumen #{$docNum} tidak boleh lebih dari 10MB.";
                $messages["documents.{$index}.file.mimes"] = "Format file pada Dokumen #{$docNum} harus berformat PDF, JPG, PNG, atau WEBP.";
                $messages["documents.{$index}.files.*.max"] = "Ukuran setiap berkas dokumen pada Dokumen #{$docNum} tidak boleh lebih dari 10MB.";
                $messages["documents.{$index}.files.*.mimes"] = "Format setiap berkas dokumen pada Dokumen #{$docNum} harus berformat PDF, JPG, PNG, atau WEBP.";
            }
        }

        return $messages;
    }
}


