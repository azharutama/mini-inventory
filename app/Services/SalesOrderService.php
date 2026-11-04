<?php

namespace App\Services;

use App\Events\SalesOrderCompleted;
use App\Exceptions\DomainException;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\StockUnit;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    /**
     * Daftar Sales Order terpaginasi dengan eager loading untuk mencegah N+1 query (PRD 8.2 & 8.3).
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return SalesOrder::query()
            ->with(['items.product:id,sku,name', 'items.stockUnits:id,sales_order_item_id,serial_number,status'])
            ->latest('transaction_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Simpan Sales Order, kunci & validasi SN, ubah status unit ke sold, dan catat mutasi keluar (Goal 2, PRD 7.1, 7.2, 7.3).
     *
     * @param  array{
     *     order_number: string,
     *     transaction_date: string,
     *     items: array<int, array{
     *         product_id: int,
     *         quantity: int,
     *         serial_numbers: array<int, string>
     *     }>
     * }  $data
     */
    public function store(array $data): SalesOrder
    {
        try {
            return DB::transaction(function () use ($data) {
                $transactionDate = Carbon::parse($data['transaction_date'])->toDateString();

                // 1. Kumpulkan seluruh SN dan validasi jumlah SN == quantity pesanan (BR-10, FR-18)
                $allSerialNumbers = [];
                foreach ($data['items'] as $item) {
                    $qty = (int) $item['quantity'];
                    $serials = array_values(array_filter(
                        array_map(fn ($sn) => strtoupper(trim((string) $sn)), $item['serial_numbers'] ?? []),
                        fn ($sn) => $sn !== ''
                    ));

                    if (count($serials) !== $qty) {
                        throw new DomainException(
                            "Jumlah Serial Number (" . count($serials) . ") harus sesuai dengan quantity pesanan ({$qty}) (BR-10)."
                        );
                    }

                    foreach ($serials as $sn) {
                        $allSerialNumbers[] = $sn;
                    }
                }

                // 2. Cek duplikasi SN di dalam input pengeluaran yang sama
                if (count($allSerialNumbers) !== count(array_unique($allSerialNumbers))) {
                    throw new DomainException('Terdapat Serial Number yang duplikat di dalam input Sales Order.');
                }

                // 3. Kunci unit dengan lockForUpdate() dalam urutan id yang konsisten untuk mencegah race condition & deadlock (PRD 7.2)
                $units = StockUnit::query()
                    ->with(['movements' => fn ($q) => $q->where('movement_type', StockMovement::TYPE_IN)])
                    ->whereIn('serial_number', $allSerialNumbers)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('serial_number');

                // 4. Validasi keberadaan SN (BR-05, FR-15)
                if ($units->count() !== count($allSerialNumbers)) {
                    $missing = array_diff($allSerialNumbers, $units->keys()->all());
                    throw new DomainException('Serial Number tidak terdaftar: ' . implode(', ', $missing) . ' (BR-05).');
                }

                // 5. Validasi status ketersediaan, kesesuaian produk, dan tanggal historis (BR-06..08, FR-16, FR-17, PRD 7.3)
                foreach ($data['items'] as $item) {
                    $expectedProductId = (int) $item['product_id'];
                    $serials = array_values(array_filter(
                        array_map(fn ($sn) => strtoupper(trim((string) $sn)), $item['serial_numbers']),
                        fn ($sn) => $sn !== ''
                    ));

                    foreach ($serials as $sn) {
                        /** @var StockUnit $unit */
                        $unit = $units->get($sn);

                        // BR-06 & BR-07: Unit harus berstatus in_stock
                        if ($unit->status !== StockUnit::STATUS_IN_STOCK) {
                            throw new DomainException("SN {$unit->serial_number} tidak tersedia / sudah terjual (BR-06/BR-07).");
                        }

                        // BR-08: SN harus sesuai dengan produk pada pesanan
                        if ((int) $unit->product_id !== $expectedProductId) {
                            throw new DomainException("SN {$unit->serial_number} tidak sesuai dengan produk yang dipilih pada pesanan (BR-08).");
                        }

                        // PRD 7.3 (Historical Stock Integrity): Tanggal keluar tidak boleh mendahului tanggal masuk unit tersebut
                        $inboundMovement = $unit->movements->first();
                        if ($inboundMovement && $transactionDate < $inboundMovement->transaction_date->toDateString()) {
                            throw new DomainException(
                                "SN {$unit->serial_number} baru diterima pada tanggal {$inboundMovement->transaction_date->format('Y-m-d')}, sehingga tidak dapat dikeluarkan pada tanggal {$transactionDate} (Historical Stock Integrity)."
                            );
                        }
                    }
                }

                // 6. Simpan header Sales Order (FR-11, FR-12)
                $salesOrder = SalesOrder::query()->create([
                    'order_number' => strtoupper(trim($data['order_number'])),
                    'transaction_date' => $transactionDate,
                ]);

                // 7. Simpan item SO, ubah status unit menjadi 'sold', dan catat stock_movements 'out' (FR-19, FR-20)
                foreach ($data['items'] as $item) {
                    $serials = array_values(array_filter(
                        array_map(fn ($sn) => strtoupper(trim((string) $sn)), $item['serial_numbers']),
                        fn ($sn) => $sn !== ''
                    ));

                    $soItem = $salesOrder->items()->create([
                        'product_id' => (int) $item['product_id'],
                        'quantity' => (int) $item['quantity'],
                    ]);

                    foreach ($serials as $sn) {
                        /** @var StockUnit $unit */
                        $unit = $units->get($sn);

                        // Atomic conditional update sebagai proteksi lapis kedua terhadap race condition
                        $affected = StockUnit::query()
                            ->whereKey($unit->id)
                            ->where('status', StockUnit::STATUS_IN_STOCK)
                            ->update([
                                'sales_order_item_id' => $soItem->id,
                                'status' => StockUnit::STATUS_SOLD,
                            ]);

                        if ($affected === 0) {
                            throw new DomainException("SN {$unit->serial_number} tidak tersedia / sudah terjual oleh transaksi lain (BR-15).");
                        }

                        StockMovement::query()->create([
                            'stock_unit_id' => $unit->id,
                            'transaction_date' => $transactionDate,
                            'movement_type' => StockMovement::TYPE_OUT,
                        ]);
                    }
                }

                // 8. Dispatch event setelah transaksi berhasil commit (PRD 6.5)
                SalesOrderCompleted::dispatch($salesOrder);

                return $salesOrder;
            }, 3);
        } catch (UniqueConstraintViolationException $e) {
            throw new DomainException('Nomor Sales Order sudah digunakan.', previous: $e);
        }
    }
}

