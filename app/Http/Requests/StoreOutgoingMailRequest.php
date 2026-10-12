<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOutgoingMailRequest extends FormRequest
{
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
        return [
            'documents' => ['required', 'array', 'min:1'],
            'documents.*.mail_number' => ['nullable', 'string', 'max:255', 'distinct:ignore_case', 'unique:outgoing_mails,mail_number'],
            'documents.*.subject' => ['required', 'string', 'max:255'],
            'documents.*.recipient' => ['required', 'string', 'max:255'],
            'documents.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'documents.*.files' => ['nullable', 'array'],
            'documents.*.files.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'documents.*.disposition_note' => ['nullable', 'string'],
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
            'documents.*.subject.required' => 'Subjek / Perihal wajib diisi.',
            'documents.*.recipient.required' => 'Penerima Surat wajib diisi.',
            'documents.*.mail_number.unique' => 'Nomor Surat ini sudah ada di dalam sistem.',
            'documents.*.file.max' => 'Ukuran berkas dokumen tidak boleh lebih dari 10MB.',
            'documents.*.file.mimes' => 'Format berkas dokumen harus PDF, JPG, JPEG, PNG, atau WEBP.',
        ];

        $documents = $this->input('documents', []);
        if (is_array($documents)) {
            foreach (array_keys($documents) as $index) {
                $docNum = (int) $index + 1;
                $messages["documents.{$index}.subject.required"] = "Subjek / Perihal pada Dokumen #{$docNum} wajib diisi.";
                $messages["documents.{$index}.recipient.required"] = "Penerima Surat pada Dokumen #{$docNum} wajib diisi.";
                $messages["documents.{$index}.file.max"] = "Ukuran file pada Dokumen #{$docNum} tidak boleh lebih dari 10MB.";
                $messages["documents.{$index}.file.mimes"] = "Format file pada Dokumen #{$docNum} harus berformat PDF, JPG, PNG, atau WEBP.";
                $messages["documents.{$index}.files.*.max"] = "Ukuran setiap berkas dokumen pada Dokumen #{$docNum} tidak boleh lebih dari 10MB.";
                $messages["documents.{$index}.files.*.mimes"] = "Format setiap berkas dokumen pada Dokumen #{$docNum} harus berformat PDF, JPG, PNG, atau WEBP.";
            }
        }

        return $messages;
    }
}

