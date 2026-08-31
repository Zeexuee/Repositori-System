<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOutgoingMailRequest extends FormRequest
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
        $outgoingMailId = $this->route('outgoing_mail')?->id ?? $this->route('outgoing_mail');

        return [
            'mail_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('outgoing_mails', 'mail_number')->ignore($outgoingMailId),
            ],
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'recipient' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:RECEIVE,RECEIVED,PROGRES,PROGRESS,IN_PROGRESS,PENDING,RETURN,RETURNED,DRAFT,APPROVED,SIGNED'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
