<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    public function run(PurchaseOrderService $purchaseOrderService): void
    {
        // Cegah duplikasi jika seeder dijalankan ulang tanpa migrate:fresh
        if (PurchaseOrder::query()->exists()) {
            return;
        }

        $lnv = Product::where('sku', 'LNV-X1')->firstOrFail();
        $dll = Product::where('sku', 'DLL-XPS13')->firstOrFail();
        $mbp = Product::where('sku', 'MBP-M3')->firstOrFail();
        $mon = Product::where('sku', 'MON-LG27')->firstOrFail();
        $log = Product::where('sku', 'LOG-MX3S')->firstOrFail();

        // 1. PO Bulan Oktober 2026 (menjadi Saldo Awal saat cek mutasi bulan November 2026)
        $purchaseOrderService->store([
            'po_number' => 'PO-202610-001',
            'transaction_date' => '2026-10-15',
            'items' => [
                [
                    'product_id' => $lnv->id,
                    'quantity' => 5,
                    'serial_numbers' => ['LNV001', 'LNV002', 'LNV003', 'LNV004', 'LNV005'],
                ],
                [
                    'product_id' => $dll->id,
                    'quantity' => 3,
                    'serial_numbers' => ['DLL001', 'DLL002', 'DLL003'],
                ],
            ],
        ]);

        $purchaseOrderService->store([
            'po_number' => 'PO-202610-002',
            'transaction_date' => '2026-10-25',
            'items' => [
                [
                    'product_id' => $mbp->id,
                    'quantity' => 3,
                    'serial_numbers' => ['MBP001', 'MBP002', 'MBP003'],
                ],
                [
                    'product_id' => $mon->id,
                    'quantity' => 4,
                    'serial_numbers' => ['MON001', 'MON002', 'MON003', 'MON004'],
                ],
            ],
        ]);

        // 2. PO Bulan November 2026 (tercatat sebagai Barang Masuk pada periode November 2026)
        $purchaseOrderService->store([
            'po_number' => 'PO-202611-001',
            'transaction_date' => '2026-11-02',
            'items' => [
                [
                    'product_id' => $lnv->id,
                    'quantity' => 3,
                    'serial_numbers' => ['LNV006', 'LNV007', 'LNV008'],
                ],
                [
                    'product_id' => $log->id,
                    'quantity' => 4,
                    'serial_numbers' => ['LOG001', 'LOG002', 'LOG003', 'LOG004'],
                ],
            ],
        ]);

        $purchaseOrderService->store([
            'po_number' => 'PO-202611-002',
            'transaction_date' => '2026-11-04',
            'items' => [
                [
                    'product_id' => $dll->id,
                    'quantity' => 2,
                    'serial_numbers' => ['DLL004', 'DLL005'],
                ],
            ],
        ]);
    }
}

