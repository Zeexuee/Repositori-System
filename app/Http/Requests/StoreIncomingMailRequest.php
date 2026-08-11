<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncomingMailRequest extends FormRequest
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
            'mail_number' => ['required', 'string', 'max:255', 'unique:incoming_mails,mail_number'],
            'subject' => ['required', 'string', 'max:255'],
            'sender' => ['required', 'string', 'max:255'],
            'received_date' => ['required', 'date'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
