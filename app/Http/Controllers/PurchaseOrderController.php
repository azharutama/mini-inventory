<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Services\ProductService;
use App\Services\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderService $purchaseOrderService,
        private readonly ProductService $productService,
    ) {}

    public function index(): View
    {
        // Tampilkan form & riwayat Purchase Order terpaginasi tanpa N+1 query (Goal 1)
        $purchaseOrders = $this->purchaseOrderService->paginate();
        $products = $this->productService->listAll();

        return view('purchase-orders.index', compact('purchaseOrders', 'products'));
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        // Simpan PO, stock_units, dan stock_movements (in) lewat Service (PRD 6.1)
        $this->purchaseOrderService->store($request->validated());

        return redirect()
            ->route('purchase-orders.index')
            ->with('success', 'Purchase Order dan penerimaan Serial Number berhasil disimpan.');
    }
}

