<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesOrderRequest;
use App\Services\ProductService;
use App\Services\SalesOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SalesOrderController extends Controller
{
    public function __construct(
        private readonly SalesOrderService $salesOrderService,
        private readonly ProductService $productService,
    ) {}

    public function index(): View
    {
        // Tampilkan form & riwayat Sales Order terpaginasi tanpa N+1 query (Goal 2)
        $salesOrders = $this->salesOrderService->paginate();
        $products = $this->productService->listAll();

        return view('sales-orders.index', compact('salesOrders', 'products'));
    }

    public function store(StoreSalesOrderRequest $request): RedirectResponse
    {
        // Simpan SO, validasi & lock SN, update sold, dan catat stock_movements (out) lewat Service (PRD 6.1)
        $this->salesOrderService->store($request->validated());

        return redirect()
            ->route('sales-orders.index')
            ->with('success', 'Sales Order dan pengeluaran Serial Number berhasil disimpan.');
    }
}

