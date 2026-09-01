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
        $isDraft = filter_var($this->input('is_draft'), FILTER_VALIDATE_BOOLEAN) || $this->input('action') === 'draft';
        $hasDocuments = is_array($this->input('documents')) && count($this->input('documents')) > 0;

        $topMailNumberRule = ($isDraft || $hasDocuments) ? ['nullable', 'string', 'max:255'] : ['sometimes', 'required', 'string', 'max:255'];
        $topSubjectRule = ($isDraft || $hasDocuments) ? ['nullable', 'string', 'max:255'] : ['sometimes', 'required', 'string', 'max:255'];

        $docMailNumberRule = $isDraft ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'];
        $docSubjectRule = $isDraft ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'];

        return [
            'is_draft' => ['nullable'],
            'action' => ['nullable', 'string'],
            'sender' => ['sometimes', 'required', 'string', 'max:255'],
            'received_date' => ['sometimes', 'required', 'date'],
            'recipient' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'receipt_signature' => ['nullable', 'string'],
            'receipt_signature_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            // Single document input fields
            'mail_number' => $topMailNumberRule,
            'subject' => $topSubjectRule,
            'status' => ['nullable', 'string', 'in:DRAFT,RECEIVE,RECEIVED,RETURN,RETURNED,PROGRES,PROGRESS,IN_PROGRESS,REGISTERED,PENDING,COMPLETED,OVERDUE,REVISI,REVISION'],
            'outgoing_date' => ['nullable', 'date'],
            'disposition_note' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'document_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],

            // Multi-document batch input fields
            'documents' => ['nullable', 'array'],
            'documents.*.id' => ['nullable', 'string'],
            'documents.*.mail_number' => $docMailNumberRule,
            'documents.*.subject' => $docSubjectRule,
            'documents.*.outgoing_date' => ['nullable', 'date'],
            'documents.*.disposition_note' => ['nullable', 'string'],
            'documents.*.notes' => ['nullable', 'string'],
            'documents.*.status' => ['nullable', 'string', 'in:DRAFT,RECEIVE,RECEIVED,RETURN,RETURNED,PROGRES,PROGRESS,IN_PROGRESS,REGISTERED,PENDING,COMPLETED,OVERDUE,REVISI,REVISION'],
            'documents.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'documents.*.document_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];
    }
}
