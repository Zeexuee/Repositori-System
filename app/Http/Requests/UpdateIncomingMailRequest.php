<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'mail_number' => ['sometimes', 'required', 'string', 'max:255'],
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'sender' => ['sometimes', 'required', 'string', 'max:255'],
            'received_date' => ['sometimes', 'required', 'date'],
            'recipient' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:RECEIVE,RECEIVED,RETURN,RETURNED,PROGRES,PROGRESS,IN_PROGRESS,REGISTERED,PENDING,COMPLETED,OVERDUE,REVISI'],
            'outgoing_date' => ['nullable', 'date'],
            'disposition_note' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'document_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'receipt_signature' => ['nullable', 'string'],
            'receipt_signature_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
