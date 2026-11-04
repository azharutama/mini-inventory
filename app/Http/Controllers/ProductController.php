<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    public function index(Request $request): View
    {
        // Tampilkan daftar produk beserta stok terkini dengan pagination (FR-01)
        $products = $this->productService->paginate($request->string('search')->toString());

        return view('products.index', compact('products'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        // Simpan produk baru melalui ProductService (FR-02)
        $this->productService->store($request->validated());

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }
}

