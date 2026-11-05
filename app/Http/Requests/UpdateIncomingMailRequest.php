<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\MailRecipient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncomingMailRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('recipient')) {
            $this->merge([
                'recipient' => MailRecipient::format($this->input('recipient')),
            ]);
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
        $incomingMail = $this->route('incoming_mail') ?? $this->route('incomingMail');
        if (! $incomingMail instanceof \App\Models\IncomingMail) {
            $incomingMailId = $incomingMail?->id ?? $incomingMail ?? $this->input('id');
            $incomingMail = $incomingMailId ? \App\Models\IncomingMail::find($incomingMailId) : null;
        }

        $mailNumberRules = ['nullable', 'string', 'max:255'];
        $submittedMailNumber = $this->input('mail_number');

        // Jika nomor surat tidak diubah, atau sama dengan nomor tanda terima (ID doc otomatis batch),
        // maka nomor tersebut diizinkan dan tidak perlu memicu validasi unik terhadap dokumen lain dalam batch/draft yang sama.
        $isSameAsCurrent = $incomingMail && filled($submittedMailNumber) && $submittedMailNumber === $incomingMail->mail_number;
        $isSameAsReceipt = $incomingMail && filled($submittedMailNumber) && $submittedMailNumber === $incomingMail->receipt_number;

        if (! $isSameAsCurrent && ! $isSameAsReceipt && filled($submittedMailNumber)) {
            $uniqueRule = Rule::unique('incoming_mails', 'mail_number')
                ->whereNull('deleted_at');

            if ($incomingMail && $incomingMail->batch_id) {
                // Abaikan dokumen-dokumen lain yang diinput bersamaan dalam batch / draft yang sama
                $uniqueRule->where(function ($query) use ($incomingMail) {
                    $query->where('batch_id', '!=', $incomingMail->batch_id)
                          ->orWhereNull('batch_id');
                })->ignore($incomingMail->id);
            } elseif ($incomingMail) {
                $uniqueRule->ignore($incomingMail->id);
            }

            $mailNumberRules[] = $uniqueRule;
        }

        return [
            'mail_number' => $mailNumberRules,
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'sender' => ['sometimes', 'required', 'string', 'max:255'],
            'received_date' => ['sometimes', 'required', 'date'],
            'recipient' => ['nullable', 'string', 'max:2000'],
            'progress' => ['nullable', 'string', 'in:RECEIVE,RECEIVED,RETURN,RETURNED,WAITING_RETURN,REVISI,DISPOSISI,DRAFT'],
            'status' => ['nullable', 'string', 'max:255'],
            'revision_note' => ['nullable', 'string', 'max:2000'],
            'outgoing_date' => ['nullable', 'date'],
            'disposition_note' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],

            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'document_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'receipt_signature' => ['nullable', 'string'],
            'receipt_signature_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'mail_number.unique' => 'Nomor Surat ini sudah ada di dalam sistem.',
        ];
    }
}

