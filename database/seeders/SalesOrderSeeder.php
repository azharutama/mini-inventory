<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Database\Seeder;

class SalesOrderSeeder extends Seeder
{
    public function run(SalesOrderService $salesOrderService): void
    {
        // Cegah duplikasi jika seeder dijalankan ulang tanpa migrate:fresh
        if (SalesOrder::query()->exists()) {
            return;
        }

        $lnv = Product::where('sku', 'LNV-X1')->firstOrFail();
        $dll = Product::where('sku', 'DLL-XPS13')->firstOrFail();
        $mbp = Product::where('sku', 'MBP-M3')->firstOrFail();
        $mon = Product::where('sku', 'MON-LG27')->firstOrFail();
        $log = Product::where('sku', 'LOG-MX3S')->firstOrFail();

        // 1. Sales Order Bulan Oktober 2026 (mengurangi Saldo Awal sebelum November 2026)
        $salesOrderService->store([
            'order_number' => 'SO-202610-001',
            'transaction_date' => '2026-10-20',
            'items' => [
                [
                    'product_id' => $lnv->id,
                    'quantity' => 2,
                    'serial_numbers' => ['LNV001', 'LNV002'],
                ],
                [
                    'product_id' => $dll->id,
                    'quantity' => 1,
                    'serial_numbers' => ['DLL001'],
                ],
            ],
        ]);

        // 2. Sales Order Bulan November 2026 (tercatat sebagai Barang Keluar pada periode November 2026)
        $salesOrderService->store([
            'order_number' => 'SO-202611-001',
            'transaction_date' => '2026-11-03',
            'items' => [
                [
                    'product_id' => $lnv->id,
                    'quantity' => 2,
                    'serial_numbers' => ['LNV003', 'LNV006'],
                ],
                [
                    'product_id' => $mbp->id,
                    'quantity' => 1,
                    'serial_numbers' => ['MBP001'],
                ],
            ],
        ]);

        $salesOrderService->store([
            'order_number' => 'SO-202611-002',
            'transaction_date' => '2026-11-04',
            'items' => [
                [
                    'product_id' => $mon->id,
                    'quantity' => 2,
                    'serial_numbers' => ['MON001', 'MON002'],
                ],
                [
                    'product_id' => $log->id,
                    'quantity' => 1,
                    'serial_numbers' => ['LOG001'],
                ],
            ],
        ]);
    }
}

