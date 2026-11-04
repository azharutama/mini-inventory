<?php

namespace App\Services;

use App\Events\PurchaseOrderReceived;
use App\Exceptions\DomainException;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\StockUnit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    /**
     * Daftar Purchase Order terpaginasi dengan eager loading untuk mencegah N+1 query (PRD 8.2 & 8.3).
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return PurchaseOrder::query()
            ->with(['items.product:id,sku,name', 'items.stockUnits:id,purchase_order_item_id,serial_number,status'])
            ->latest('transaction_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Simpan Purchase Order, unit SN (in_stock), dan mutasi masuk (in) dalam 1 transaksi atomik (Goal 1, PRD 6.2 & 7.1).
     *
     * @param  array{
     *     po_number: string,
     *     transaction_date: string,
     *     items: array<int, array{
     *         product_id: int,
     *         quantity: int,
     *         serial_numbers: array<int, string>
     *     }>
     * }  $data
     */
    public function store(array $data): PurchaseOrder
    {
        try {
            return DB::transaction(function () use ($data) {
                // 1. Kumpulkan seluruh SN dan validasi jumlah SN == quantity (BR-01, BR-09, FR-06, FR-07)
                $allSerialNumbers = [];
                foreach ($data['items'] as $item) {
                    $qty = (int) $item['quantity'];
                    $serials = array_values(array_filter(
                        array_map(fn ($sn) => strtoupper(trim((string) $sn)), $item['serial_numbers'] ?? []),
                        fn ($sn) => $sn !== ''
                    ));

                    if (count($serials) !== $qty) {
                        throw new DomainException(
                            "Jumlah Serial Number (" . count($serials) . ") harus sesuai dengan quantity penerimaan ({$qty}) (BR-09)."
                        );
                    }

                    foreach ($serials as $sn) {
                        $allSerialNumbers[] = $sn;
                    }
                }

                // 2. Cek duplikasi SN di dalam input transaksi yang sama
                if (count($allSerialNumbers) !== count(array_unique($allSerialNumbers))) {
                    throw new DomainException('Terdapat Serial Number yang duplikat di dalam input Purchase Order (BR-01).');
                }

                // 3. Cek ulang apakah ada SN yang sudah terdaftar di database
                $existing = StockUnit::query()
                    ->whereIn('serial_number', $allSerialNumbers)
                    ->lockForUpdate()
                    ->pluck('serial_number')
                    ->all();

                if ($existing !== []) {
                    throw new DomainException('Serial Number berikut sudah terdaftar: ' . implode(', ', $existing) . ' (BR-02).');
                }

                // 4. Simpan header Purchase Order (FR-03, FR-04, FR-08)
                $po = PurchaseOrder::query()->create([
                    'po_number' => strtoupper(trim($data['po_number'])),
                    'transaction_date' => $data['transaction_date'],
                ]);

                // 5. Simpan detail item, stock_units (status in_stock), dan stock_movements (movement_type = in) (FR-08..FR-10)
                foreach ($data['items'] as $item) {
                    $serials = array_values(array_filter(
                        array_map(fn ($sn) => strtoupper(trim((string) $sn)), $item['serial_numbers']),
                        fn ($sn) => $sn !== ''
                    ));

                    $poItem = $po->items()->create([
                        'product_id' => (int) $item['product_id'],
                        'quantity' => (int) $item['quantity'],
                    ]);

                    foreach ($serials as $sn) {
                        $unit = StockUnit::query()->create([
                            'product_id' => (int) $item['product_id'],
                            'purchase_order_item_id' => $poItem->id,
                            'sales_order_item_id' => null,
                            'serial_number' => $sn,
                            'status' => StockUnit::STATUS_IN_STOCK,
                        ]);

                        StockMovement::query()->create([
                            'stock_unit_id' => $unit->id,
                            'transaction_date' => $data['transaction_date'],
                            'movement_type' => StockMovement::TYPE_IN,
                        ]);
                    }
                }

                // 6. Dispatch event setelah transaksi berhasil commit (PRD 6.5)
                PurchaseOrderReceived::dispatch($po);

                return $po;
            }, 3);
        } catch (UniqueConstraintViolationException $e) {
            throw new DomainException('Nomor PO atau Serial Number sudah digunakan pada transaksi lain.', previous: $e);
        }
    }
}

