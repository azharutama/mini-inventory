<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Default tanggal awal & akhir ke bulan berjalan jika belum dipilih.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_date' => $this->input('start_date') ?: now()->startOfMonth()->toDateString(),
            'end_date' => $this->input('end_date') ?: now()->endOfMonth()->toDateString(),
        ]);
    }

    /**
     * Validasi filter tanggal awal dan akhir laporan mutasi stok (PRD 6.3 & FR-23).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Tanggal awal wajib diisi.',
            'start_date.date' => 'Format tanggal awal tidak valid.',
            'end_date.required' => 'Tanggal akhir wajib diisi.',
            'end_date.date' => 'Format tanggal akhir tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal awal.',
        ];
    }
}

