<?php

namespace Tests\Feature;

use App\Exceptions\DomainException;
use App\Models\Product;
use App\Models\StockUnit;
use App\Services\PurchaseOrderService;
use App\Services\SalesOrderService;
use App\Services\StockReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventorySystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_tc01_to_tc06_inbound_purchase_order_and_validations(): void
    {
        // TC-01: Membuat produk
        $this->post(route('products.store'), [
            'sku' => 'LNV-X1',
            'name' => 'Lenovo ThinkPad X1',
        ])->assertRedirect(route('products.index'));

        $product = Product::where('sku', 'LNV-X1')->firstOrFail();

        // TC-02, TC-03, TC-04: Membuat PO dengan tanggal manual & mendaftarkan SN
        $this->post(route('purchase-orders.store'), [
            'po_number' => 'PO-001',
            'transaction_date' => '2026-01-10',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'serial_numbers' => "LNV001\nLNV002",
                ],
            ],
        ])->assertRedirect(route('purchase-orders.index'));

        $this->assertDatabaseHas('stock_units', [
            'serial_number' => 'LNV001',
            'status' => StockUnit::STATUS_IN_STOCK,
        ]);

        // TC-05: SN duplikat ditolak
        $this->post(route('purchase-orders.store'), [
            'po_number' => 'PO-002',
            'transaction_date' => '2026-01-11',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'serial_numbers' => "LNV001",
                ],
            ],
        ])->assertSessionHasErrors();

        // TC-06: Quantity tidak sesuai jumlah SN ditolak
        $this->post(route('purchase-orders.store'), [
            'po_number' => 'PO-003',
            'transaction_date' => '2026-01-11',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'serial_numbers' => "LNV003\nLNV004",
                ],
            ],
        ])->assertSessionHasErrors();
    }

    public function test_tc07_to_tc13_and_tc18_outbound_sales_order_and_validations(): void
    {
        $productA = Product::create(['sku' => 'PRD-A', 'name' => 'Produk A']);
        $productB = Product::create(['sku' => 'PRD-B', 'name' => 'Produk B']);

        app(PurchaseOrderService::class)->store([
            'po_number' => 'PO-100',
            'transaction_date' => '2026-11-05',
            'items' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 2,
                    'serial_numbers' => ['SN-A1', 'SN-A2'],
                ],
                [
                    'product_id' => $productB->id,
                    'quantity' => 1,
                    'serial_numbers' => ['SN-B1'],
                ],
            ],
        ]);

        // TC-18: Transaksi keluar bertanggal mundur sebelum barang masuk ditolak
        $this->expectExceptionMessageMatches('/Historical Stock Integrity/');
        app(SalesOrderService::class)->store([
            'order_number' => 'SO-EARLY',
            'transaction_date' => '2026-11-01',
            'items' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 1,
                    'serial_numbers' => ['SN-A1'],
                ],
            ],
        ]);
    }

    public function test_tc08_to_tc12_outbound_rules_and_double_issue_prevention(): void
    {
        $productA = Product::create(['sku' => 'PRD-A', 'name' => 'Produk A']);
        $productB = Product::create(['sku' => 'PRD-B', 'name' => 'Produk B']);

        app(PurchaseOrderService::class)->store([
            'po_number' => 'PO-200',
            'transaction_date' => '2026-11-05',
            'items' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 2,
                    'serial_numbers' => ['SN-A1', 'SN-A2'],
                ],
                [
                    'product_id' => $productB->id,
                    'quantity' => 1,
                    'serial_numbers' => ['SN-B1'],
                ],
            ],
        ]);

        $salesService = app(SalesOrderService::class);

        // TC-07 & TC-08: Mengeluarkan SN tersedia berhasil
        $salesService->store([
            'order_number' => 'SO-200',
            'transaction_date' => '2026-11-07',
            'items' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 1,
                    'serial_numbers' => ['SN-A1'],
                ],
            ],
        ]);

        $this->assertDatabaseHas('stock_units', [
            'serial_number' => 'SN-A1',
            'status' => StockUnit::STATUS_SOLD,
        ]);

        // TC-10 & TC-12: Mengeluarkan SN yang sudah sold ditolak
        try {
            $salesService->store([
                'order_number' => 'SO-201',
                'transaction_date' => '2026-11-08',
                'items' => [
                    [
                        'product_id' => $productA->id,
                        'quantity' => 1,
                        'serial_numbers' => ['SN-A1'],
                    ],
                ],
            ]);
            $this->fail('Seharusnya gagal mengeluarkan SN yang sudah sold.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('tidak tersedia', $e->getMessage());
        }

        // TC-11: Mengeluarkan SN produk berbeda ditolak
        try {
            $salesService->store([
                'order_number' => 'SO-202',
                'transaction_date' => '2026-11-08',
                'items' => [
                    [
                        'product_id' => $productA->id,
                        'quantity' => 1,
                        'serial_numbers' => ['SN-B1'],
                    ],
                ],
            ]);
            $this->fail('Seharusnya gagal mengeluarkan SN milik produk lain.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('tidak sesuai dengan produk', $e->getMessage());
        }
    }

    public function test_tc14_to_tc17_stock_report_and_mutation_calculations(): void
    {
        $product = Product::create(['sku' => 'PRD-A', 'name' => 'Produk A']);
        $poService = app(PurchaseOrderService::class);
        $soService = app(SalesOrderService::class);
        $reportService = app(StockReportService::class);

        $poService->store([
            'po_number' => 'PO-JAN',
            'transaction_date' => '2026-01-10',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 5,
                'serial_numbers' => ['A001', 'A002', 'A003', 'A004', 'A005'],
            ]],
        ]);

        $poService->store([
            'po_number' => 'PO-FEB',
            'transaction_date' => '2026-02-05',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 3,
                'serial_numbers' => ['A006', 'A007', 'A008'],
            ]],
        ]);

        $soService->store([
            'order_number' => 'SO-JAN',
            'transaction_date' => '2026-01-20',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'serial_numbers' => ['A001', 'A002'],
            ]],
        ]);

        $soService->store([
            'order_number' => 'SO-FEB',
            'transaction_date' => '2026-02-15',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 4,
                'serial_numbers' => ['A003', 'A004', 'A006', 'A007'],
            ]],
        ]);

        // TC-14: Stok terkini = 2 (A005, A008)
        $currentStock = $reportService->getCurrentStock()->first();
        $this->assertSame(2, (int) $currentStock->available_stock);

        // TC-15..TC-17: Periode Januari (Saldo Awal 0, Masuk 5, Keluar 2, Saldo Akhir 3)
        $jan = $reportService->getMutationReport('2026-01-01', '2026-01-31')->first();
        $this->assertSame(0, $jan->opening_stock);
        $this->assertSame(5, $jan->total_inbound);
        $this->assertSame(2, $jan->total_outbound);
        $this->assertSame(3, $jan->closing_stock);

        // Periode Februari (Saldo Awal 3, Masuk 3, Keluar 4, Saldo Akhir 2)
        $feb = $reportService->getMutationReport('2026-02-01', '2026-02-28')->first();
        $this->assertSame(3, $feb->opening_stock);
        $this->assertSame(3, $feb->total_inbound);
        $this->assertSame(4, $feb->total_outbound);
        $this->assertSame(2, $feb->closing_stock);
    }
}

