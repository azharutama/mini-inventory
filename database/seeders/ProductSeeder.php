<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Daftar master produk awal (idempotent dengan updateOrCreate)
        $products = [
            ['sku' => 'LNV-X1', 'name' => 'Lenovo ThinkPad X1 Carbon'],
            ['sku' => 'DLL-XPS13', 'name' => 'Dell XPS 13 Plus'],
            ['sku' => 'MBP-M3', 'name' => 'MacBook Pro 14 M3'],
            ['sku' => 'mon-lg27', 'name' => 'Monitor LG UltraFine 27 Inch 4K'],
            ['sku' => 'log-mx3s', 'name' => 'Logitech MX Master 3S Wireless'],
        ];

        foreach ($products as $item) {
            Product::query()->updateOrCreate(
                ['sku' => strtoupper($item['sku'])],
                ['name' => $item['name']]
            );
        }
    }
}

