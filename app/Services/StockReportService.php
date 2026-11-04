<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockUnit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockReportService
{
    /**
     * Cek Stok Terkini per produk berdasarkan unit berstatus 'in_stock' (FR-21, FR-22, PRD 8.4).
     */
    public function getCurrentStock(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $search = trim((string) $search);

        return Product::query()
            ->select(['id', 'sku', 'name'])
            ->withCount([
                'stockUnits as available_stock' => fn ($query) => $query->where('status', StockUnit::STATUS_IN_STOCK),
                'stockUnits as sold_stock' => fn ($query) => $query->where('status', StockUnit::STATUS_SOLD),
            ])
            ->with([
                'stockUnits' => fn ($query) => $query
                    ->select(['id', 'product_id', 'serial_number', 'status'])
                    ->where('status', StockUnit::STATUS_IN_STOCK)
                    ->orderBy('serial_number'),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('sku')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Laporan Mutasi Stok per Periode menggunakan conditional aggregation SQL (FR-23..FR-28, BR-11, BR-12, PRD 8.5).
     * Rumus:
     * - Saldo Awal = Total masuk sebelum periode - Total keluar sebelum periode
     * - Saldo Akhir = Saldo Awal + Total masuk periode - Total keluar periode
     */
    public function getMutationReport(string $startDate, string $endDate, int $perPage = 15): LengthAwarePaginator
    {
        $paginator = Product::query()
            ->select(['products.id', 'products.sku', 'products.name'])
            ->selectRaw(
                "COALESCE(SUM(CASE
                    WHEN sm.transaction_date < ? AND sm.movement_type = 'in' THEN 1
                    WHEN sm.transaction_date < ? AND sm.movement_type = 'out' THEN -1
                    ELSE 0
                END), 0) AS opening_stock",
                [$startDate, $startDate]
            )
            ->selectRaw(
                "COALESCE(SUM(CASE
                    WHEN sm.transaction_date BETWEEN ? AND ? AND sm.movement_type = 'in' THEN 1
                    ELSE 0
                END), 0) AS total_inbound",
                [$startDate, $endDate]
            )
            ->selectRaw(
                "COALESCE(SUM(CASE
                    WHEN sm.transaction_date BETWEEN ? AND ? AND sm.movement_type = 'out' THEN 1
                    ELSE 0
                END), 0) AS total_outbound",
                [$startDate, $endDate]
            )
            ->leftJoin('stock_units as su', 'su.product_id', '=', 'products.id')
            ->leftJoin('stock_movements as sm', 'sm.stock_unit_id', '=', 'su.id')
            ->groupBy('products.id', 'products.sku', 'products.name')
            ->orderBy('products.sku')
            ->paginate($perPage)
            ->withQueryString();

        // Hitung Saldo Akhir = Saldo Awal + Masuk - Keluar (BR-11)
        $paginator->getCollection()->transform(function ($row) {
            $row->opening_stock = (int) $row->opening_stock;
            $row->total_inbound = (int) $row->total_inbound;
            $row->total_outbound = (int) $row->total_outbound;
            $row->closing_stock = $row->opening_stock + $row->total_inbound - $row->total_outbound;

            return $row;
        });

        return $paginator;
    }
}
