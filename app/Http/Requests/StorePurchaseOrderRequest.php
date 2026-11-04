<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalisasi input Nomor PO dan pecah textarea Serial Number menjadi array (PRD 6.3).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('po_number')) {
            $this->merge([
                'po_number' => strtoupper(trim((string) $this->input('po_number'))),
            ]);
        }

        $items = $this->input('items', []);
        if (! is_array($items)) {
            return;
        }

        $normalized = [];
        foreach ($items as $idx => $item) {
            if (! is_array($item)) {
                continue;
            }

            $rawSerials = $item['serial_numbers'] ?? [];
            if (is_string($rawSerials)) {
                $lines = preg_split('/[\r\n,]+/', $rawSerials) ?: [];
            } elseif (is_array($rawSerials)) {
                $lines = $rawSerials;
            } else {
                $lines = [];
            }

            $serials = array_values(array_filter(
                array_map(fn ($sn) => strtoupper(trim((string) $sn)), $lines),
                fn (string $sn) => $sn !== ''
            ));

            $normalized[$idx] = [
                'product_id' => $item['product_id'] ?? null,
                'quantity' => $item['quantity'] ?? count($serials),
                'serial_numbers' => $serials,
            ];
        }

        $this->merge(['items' => array_values($normalized)]);
    }

    /**
     * Validasi input pembuatan Purchase Order & penerimaan SN (PRD 6.3).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'po_number' => ['required', 'string', 'max:50', 'unique:purchase_orders,po_number'],
            'transaction_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.serial_numbers' => ['required', 'array', 'min:1'],
            'items.*.serial_numbers.*' => [
                'required',
                'string',
                'max:100',
                'distinct',
                'unique:stock_units,serial_number',
            ],
        ];
    }

    /**
     * Validasi tambahan: jumlah SN pada setiap baris harus sama dengan quantity (BR-09, FR-06, TC-06).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $items = $this->input('items', []);
                if (! is_array($items)) {
                    return;
                }

                foreach ($items as $idx => $item) {
                    $qty = (int) ($item['quantity'] ?? 0);
                    $serials = is_array($item['serial_numbers'] ?? null) ? $item['serial_numbers'] : [];

                    if ($qty > 0 && count($serials) !== $qty) {
                        $validator->errors()->add(
                            "items.{$idx}.serial_numbers",
                            "Baris #" . ($idx + 1) . ": Jumlah Serial Number (" . count($serials) . ") harus sesuai dengan quantity ({$qty}) (BR-09)."
                        );
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'po_number.required' => 'Nomor PO wajib diisi.',
            'po_number.unique' => 'Nomor PO sudah terdaftar.',
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'items.required' => 'Minimal harus ada 1 item produk.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.product_id.exists' => 'Produk tidak ditemukan.',
            'items.*.quantity.required' => 'Quantity wajib diisi.',
            'items.*.quantity.min' => 'Quantity minimal 1.',
            'items.*.serial_numbers.required' => 'Serial Number wajib diisi.',
            'items.*.serial_numbers.*.distinct' => 'Serial Number :input duplikat di dalam input.',
            'items.*.serial_numbers.*.unique' => 'Serial Number :input sudah terdaftar di sistem.',
        ];
    }
}

