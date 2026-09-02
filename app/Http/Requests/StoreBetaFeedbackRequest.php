<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBetaFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'in:BUG,UI_UX,PERFORMANCE,OTHER'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'page_url' => ['nullable', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Kategori laporan wajib dipilih.',
            'category.in' => 'Kategori laporan tidak valid.',
            'title.required' => 'Judul masalah wajib diisi.',
            'title.max' => 'Judul masalah tidak boleh lebih dari 255 karakter.',
            'description.required' => 'Deskripsi error / masukan wajib diisi.',
            'attachment.mimes' => 'Lampiran harus berupa gambar (JPG, PNG, WEBP) atau PDF.',
            'attachment.max' => 'Ukuran berkas lampiran tidak boleh melebihi 10MB.',
        ];
    }
}
