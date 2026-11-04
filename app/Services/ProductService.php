<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockUnit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    /**
     * Ambil daftar produk terpaginasi beserta jumlah stok tersedia (FR-01, FR-21, FR-22, PRD 8.4).
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $search = trim((string) $search);

        return Product::query()
            ->select(['id', 'sku', 'name', 'created_at'])
            ->withCount([
                'stockUnits as available_stock' => fn ($q) => $q->where('status', StockUnit::STATUS_IN_STOCK),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Ambil daftar produk ringkas untuk pilihan dropdown form PO & SO.
     *
     * @return Collection<int, Product>
     */
    public function listAll(): Collection
    {
        return Product::query()
            ->select(['id', 'sku', 'name'])
            ->orderBy('sku')
            ->get();
    }

    /**
     * Simpan produk baru (FR-02).
     *
     * @param  array{sku: string, name: string}  $data
     */
    public function store(array $data): Product
    {
        return Product::query()->create([
            'sku' => strtoupper(trim($data['sku'])),
            'name' => trim($data['name']),
        ]);
    }
}

