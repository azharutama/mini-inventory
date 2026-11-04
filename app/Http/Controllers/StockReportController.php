<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockReportRequest;
use App\Services\StockReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockReportController extends Controller
{
    public function __construct(
        private readonly StockReportService $stockReportService
    ) {}

    public function currentStock(Request $request): View
    {
        // Cek Stok Terkini per produk (unit berstatus in_stock)
        $products = $this->stockReportService->getCurrentStock($request->string('search')->toString());

        return view('reports.current-stock', compact('products'));
    }

    public function mutation(StockReportRequest $request): View
    {
        // Laporan Mutasi Stok per Periode (Saldo Awal, Masuk, Keluar, Saldo Akhir)
        $validated = $request->validated();
        $mutations = $this->stockReportService->getMutationReport(
            $validated['start_date'],
            $validated['end_date']
        );

        return view('reports.mutation', [
            'mutations' => $mutations,
            'startDate' => $validated['start_date'],
            'endDate' => $validated['end_date'],
        ]);
    }
}
