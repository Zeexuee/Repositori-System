<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncomingMailRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $incomingMailId = $this->route('incoming_mail')?->id ?? $this->route('incoming_mail');

        return [
            'mail_number' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('incoming_mails', 'mail_number')->ignore($incomingMailId),
            ],
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'sender' => ['sometimes', 'required', 'string', 'max:255'],
            'recipient' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:RECEIVED,REGISTERED,PENDING_DISPOSITION,IN_PROGRESS,COMPLETED,OVERDUE'],
            'received_date' => ['sometimes', 'required', 'date'],
            'outgoing_date' => ['nullable', 'date'],
            'disposition_note' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'document_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'receipt_signature' => ['nullable', 'string'],
            'receipt_signature_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
