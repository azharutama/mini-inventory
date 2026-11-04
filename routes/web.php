<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\StockReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('purchase-orders.index'));

// Master Produk (FR-01, FR-02)
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::post('/products', [ProductController::class, 'store'])->name('products.store');

// GOAL 1: Purchase Order & Penerimaan Barang (Inbound) (FR-03 s.d. FR-10)
Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');

// GOAL 2: Penjualan & Pengeluaran Barang (Outbound) (FR-11 s.d. FR-20)
Route::get('/sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
Route::post('/sales-orders', [SalesOrderController::class, 'store'])->name('sales-orders.store');

// GOAL 3: Cek Stok Terkini & Mutasi Stok per Periode (FR-21 s.d. FR-28)
Route::get('/reports/current-stock', [StockReportController::class, 'currentStock'])->name('reports.current-stock');
Route::get('/reports/mutation', [StockReportController::class, 'mutation'])->name('reports.mutation');
