<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Jalankan seeder secara berurutan: Master Produk -> Inbound PO -> Outbound SO
        $this->call([
            ProductSeeder::class,
            PurchaseOrderSeeder::class,
            SalesOrderSeeder::class,
        ]);
    }
}
