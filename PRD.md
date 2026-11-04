# PRODUCT REQUIREMENTS DOCUMENT (PRD)
# Mini Inventory & Order Management System

**Versi:** 1.1  
**Status:** Draft  
**Platform:** Web Application  
**Backend:** Laravel / PHP  
**Frontend:** Blade + Tailwind CSS  
**Database:** MySQL (InnoDB)

---

# 1. Project Overview

## 1.1. Latar Belakang

Perusahaan distribusi barang membutuhkan sistem yang dapat mengelola persediaan dan pergerakan barang secara terstruktur. Setiap barang yang masuk dan keluar gudang harus dapat ditelusuri hingga ke unit fisiknya melalui Serial Number (SN).

Sistem harus mampu mengelola transaksi pembelian, penerimaan barang, penjualan, pengeluaran barang, serta menyediakan laporan mutasi stok berdasarkan periode tertentu.

## 1.2. Tujuan Produk

Membangun aplikasi web inventory dan order management yang mampu:

1. Mengelola pembelian dan penerimaan barang berdasarkan Serial Number.
2. Mengelola penjualan dan pengeluaran barang berdasarkan Serial Number.
3. Menyediakan informasi stok terkini.
4. Menyediakan laporan mutasi stok berdasarkan rentang tanggal.
5. Menjaga konsistensi data inventory.
6. Memastikan setiap unit barang dapat dilacak dari penerimaan hingga pengeluaran.

## 1.3. Target Pengguna

**Admin / Staff Gudang**

- Mengelola master produk.
- Membuat Purchase Order.
- Mencatat penerimaan barang.
- Mendaftarkan Serial Number.
- Membuat Sales Order.
- Mencatat pengeluaran barang.
- Memantau stok.
- Melihat laporan mutasi stok.

---

# 2. Scope Produk

## 2.1. In Scope

1. Master data produk.
2. Purchase Order.
3. Penerimaan barang (Inbound).
4. Pencatatan Serial Number.
5. Sales Order.
6. Pengeluaran barang (Outbound).
7. Validasi ketersediaan Serial Number.
8. Cek stok terkini.
9. Laporan mutasi stok per periode.
10. Pemilihan tanggal transaksi secara manual.
11. Validasi dan pengamanan transaksi inventory.
12. Optimasi query dan database.

## 2.2. Out of Scope

- Integrasi pembayaran.
- Integrasi ekspedisi.
- Multi-gudang.
- Manajemen supplier secara terperinci.
- Manajemen pelanggan secara terperinci.
- Akuntansi dan laporan keuangan.
- Barcode scanner.
- Integrasi pihak ketiga yang tidak dibutuhkan dalam asesmen.

---

# 3. Business Rules

| ID | Business Rule |
|---|---|
| BR-01 | Setiap unit fisik wajib memiliki satu Serial Number unik. |
| BR-02 | SN tidak boleh digunakan lebih dari satu kali. |
| BR-03 | Transaksi pembelian wajib menyediakan input tanggal manual. |
| BR-04 | Transaksi penjualan wajib menyediakan input tanggal manual. |
| BR-05 | Barang keluar harus memiliki SN yang terdaftar. |
| BR-06 | Barang keluar harus berstatus tersedia. |
| BR-07 | SN yang sudah terjual tidak boleh dikeluarkan kembali. |
| BR-08 | SN harus sesuai dengan produk pada pesanan. |
| BR-09 | Jumlah SN masuk harus sesuai dengan quantity penerimaan. |
| BR-10 | Jumlah SN keluar harus sesuai dengan quantity pesanan. |
| BR-11 | Saldo akhir = Saldo Awal + Masuk - Keluar. |
| BR-12 | Perhitungan mutasi menggunakan transaction_date. |
| BR-13 | Seluruh proses transaksi inventory harus menjaga konsistensi database. |
| BR-14 | Perubahan stok harus dilakukan melalui business logic yang terpusat. |
| BR-15 | Pengeluaran barang harus terlindungi dari concurrent requests. |
| BR-16 | Histori mutasi stok tidak boleh diubah atau dihapus sembarangan. |

---

# 4. Functional Requirements

## 4.1. GOAL 1 — Purchase Order & Penerimaan Barang (Inbound)

**Tujuan:** Mencatat pembelian dan penerimaan barang ke gudang beserta Serial Number setiap unit.

| ID | Requirement |
|---|---|
| FR-01 | Sistem dapat menampilkan daftar produk. |
| FR-02 | Admin dapat menambahkan produk. |
| FR-03 | Admin dapat membuat Purchase Order. |
| FR-04 | Admin dapat menentukan tanggal transaksi secara manual. |
| FR-05 | Admin dapat memasukkan SN setiap unit barang. |
| FR-06 | Sistem memvalidasi jumlah SN sesuai quantity. |
| FR-07 | Sistem menolak SN yang duplikat. |
| FR-08 | Sistem menyimpan PO dan detail pembelian. |
| FR-09 | Sistem mencatat setiap unit ke stock_units. |
| FR-10 | Sistem mencatat mutasi barang masuk. |

**Acceptance Criteria:**

- PO berhasil dibuat dengan nomor unik.
- Tanggal transaksi sesuai input.
- Semua unit memiliki SN unik.
- SN duplikat ditolak.
- Jumlah unit tersimpan sesuai quantity.
- Status awal unit adalah `in_stock`.
- Histori barang masuk berhasil dicatat.

## 4.2. GOAL 2 — Penjualan & Pengeluaran Barang (Outbound)

**Tujuan:** Mencatat pesanan pelanggan dan mengontrol pengeluaran barang berdasarkan SN.

| ID | Requirement |
|---|---|
| FR-11 | Admin dapat membuat Sales Order. |
| FR-12 | Admin dapat menentukan tanggal transaksi manual. |
| FR-13 | Admin dapat memilih produk dan quantity. |
| FR-14 | Admin dapat memasukkan SN barang keluar. |
| FR-15 | Sistem memvalidasi keberadaan SN. |
| FR-16 | Sistem memvalidasi status ketersediaan unit. |
| FR-17 | Sistem memvalidasi kesesuaian produk dan SN. |
| FR-18 | Sistem memvalidasi jumlah SN sesuai quantity. |
| FR-19 | Sistem mengubah status unit menjadi `sold`. |
| FR-20 | Sistem mencatat mutasi barang keluar. |

**Acceptance Criteria:**

- Sales Order berhasil disimpan.
- SN yang tidak terdaftar ditolak.
- SN yang sudah terjual ditolak.
- SN dari produk berbeda ditolak.
- Quantity sesuai jumlah SN.
- Status unit berubah menjadi `sold`.
- Histori barang keluar tercatat.

## 4.3. GOAL 3 — Cek Stok & Mutasi Stok

**Tujuan:** Memantau ketersediaan stok dan mengetahui pergerakan barang berdasarkan periode.

### A. Cek Stok Terkini

| ID | Requirement |
|---|---|
| FR-21 | Sistem menampilkan stok setiap produk. |
| FR-22 | Sistem menghitung stok berdasarkan unit berstatus `in_stock`. |

### B. Mutasi Stok per Periode

| ID | Requirement |
|---|---|
| FR-23 | Admin dapat menentukan tanggal awal dan akhir. |
| FR-24 | Sistem menghitung saldo awal. |
| FR-25 | Sistem menghitung total barang masuk. |
| FR-26 | Sistem menghitung total barang keluar. |
| FR-27 | Sistem menghitung saldo akhir. |
| FR-28 | Sistem menampilkan laporan mutasi per produk. |

Rumus:

**Saldo Awal**

Total masuk sebelum periode - Total keluar sebelum periode.

**Saldo Akhir**

Saldo awal + Total masuk periode - Total keluar periode.

**Acceptance Criteria:**

- Filter tanggal berfungsi.
- Saldo awal menghitung transaksi sebelum tanggal awal.
- Barang masuk dan keluar hanya menghitung transaksi dalam periode.
- Saldo akhir sesuai rumus.
- Tanggal transaksi digunakan sebagai dasar perhitungan.

---

# 5. Data Requirements

Database menggunakan MySQL dengan storage engine InnoDB.

## 5.1. Products

| Field | Type | Keterangan |
|---|---|---|
| id | BIGINT | Primary Key |
| sku | VARCHAR | Unique SKU |
| name | VARCHAR | Nama produk |
| timestamps | TIMESTAMP | Waktu data |

## 5.2. Purchase Orders

| Field | Type | Keterangan |
|---|---|---|
| id | BIGINT | Primary Key |
| po_number | VARCHAR | Unique PO |
| transaction_date | DATE | Tanggal transaksi |
| timestamps | TIMESTAMP | Waktu data |

## 5.3. Purchase Order Items

| Field | Type | Keterangan |
|---|---|---|
| id | BIGINT | Primary Key |
| purchase_order_id | BIGINT | Foreign Key |
| product_id | BIGINT | Foreign Key |
| quantity | INTEGER | Jumlah barang |

## 5.4. Stock Units

| Field | Type | Keterangan |
|---|---|---|
| id | BIGINT | Primary Key |
| product_id | BIGINT | Foreign Key |
| purchase_order_item_id | BIGINT | Referensi penerimaan |
| sales_order_item_id | BIGINT | Referensi pengeluaran |
| serial_number | VARCHAR | Unique SN |
| status | ENUM | in_stock / sold |

## 5.5. Sales Orders

| Field | Type | Keterangan |
|---|---|---|
| id | BIGINT | Primary Key |
| order_number | VARCHAR | Unique order |
| transaction_date | DATE | Tanggal transaksi |
| timestamps | TIMESTAMP | Waktu data |

## 5.6. Sales Order Items

| Field | Type | Keterangan |
|---|---|---|
| id | BIGINT | Primary Key |
| sales_order_id | BIGINT | Foreign Key |
| product_id | BIGINT | Foreign Key |
| quantity | INTEGER | Jumlah barang |

## 5.7. Stock Movements

| Field | Type | Keterangan |
|---|---|---|
| id | BIGINT | Primary Key |
| stock_unit_id | BIGINT | Foreign Key |
| transaction_date | DATE | Tanggal mutasi |
| movement_type | ENUM | in / out |
| timestamps | TIMESTAMP | Waktu data |

---

# 6. Engineering Standards & Backend Architecture Rules

Bagian ini merupakan standar wajib selama proses pengembangan untuk menjaga maintainability, scalability, data integrity, dan performa aplikasi.

## 6.1. Controller Responsibilities

**Rule: Controller harus bersifat thin controller dan tidak boleh menampung business logic.**

Tanggung jawab controller hanya:

- Menerima HTTP request.
- Memanggil Form Request untuk validasi.
- Memanggil Service untuk menjalankan business logic.
- Mengembalikan response atau redirect.
- Mengirimkan data ke view.

Controller dilarang:

- Menulis query database yang kompleks.
- Menampung proses perhitungan stok.
- Menangani proses transaksi database yang panjang.
- Menulis business logic secara langsung.
- Mengimplementasikan proses pengeluaran dan penerimaan barang secara keseluruhan.

Contoh struktur:

```php
public function store(StorePurchaseOrderRequest $request)
{
    $this->purchaseOrderService->store(
        $request->validated()
    );

    return redirect()
        ->back()
        ->with('success', 'Purchase Order berhasil dibuat.');
}
```

## 6.2. Service Layer

**Rule: Seluruh business logic harus ditempatkan pada Service Layer.**

Service bertanggung jawab atas:

- Proses pembuatan Purchase Order.
- Proses penerimaan barang.
- Pendaftaran Serial Number.
- Proses Sales Order.
- Validasi ketersediaan unit berdasarkan kondisi bisnis.
- Pengeluaran barang.
- Perubahan status stok.
- Perhitungan mutasi stok.
- Pengelolaan database transaction.

Struktur:

```text
app/
└── Services/
    ├── PurchaseOrderService.php
    ├── SalesOrderService.php
    └── StockReportService.php
```

Ketentuan:

1. Business logic tidak boleh diduplikasi di beberapa controller.
2. Logic yang digunakan berulang harus diekstrak ke method atau service yang sesuai.
3. Service bertanggung jawab menjaga konsistensi proses bisnis.
4. Database transaction harus dikelola pada batas proses bisnis yang membutuhkan atomicity.
5. Service tidak boleh bergantung pada HTTP Request secara langsung.

Contoh:

```php
class PurchaseOrderService
{
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {

            // Membuat Purchase Order
            // Menyimpan detail pembelian
            // Mendaftarkan setiap SN
            // Mencatat stock movement

        });
    }
}
```

## 6.3. Form Request Validation

**Rule: Validasi input HTTP harus menggunakan Laravel Form Request.**

Jangan menumpuk validasi menggunakan `$request->validate()` di setiap controller.

Buat Form Request terpisah:

```text
app/
└── Http/
    └── Requests/
        ├── StorePurchaseOrderRequest.php
        ├── StoreSalesOrderRequest.php
        └── StockReportRequest.php
```

Tanggung jawab Form Request:

- Validasi required fields.
- Validasi format tanggal.
- Validasi format data.
- Validasi unique fields.
- Validasi keberadaan foreign key.
- Validasi array dan nested input.
- Validasi jumlah SN.
- Validasi tanggal awal dan akhir laporan.

Contoh:

```php
public function rules(): array
{
    return [
        'po_number' => [
            'required',
            'string',
            'unique:purchase_orders,po_number',
        ],

        'transaction_date' => [
            'required',
            'date',
        ],

        'items' => [
            'required',
            'array',
            'min:1',
        ],

        'items.*.product_id' => [
            'required',
            'exists:products,id',
        ],

        'items.*.serial_numbers' => [
            'required',
            'array',
            'min:1',
        ],

        'items.*.serial_numbers.*' => [
            'required',
            'string',
            'distinct',
            'unique:stock_units,serial_number',
        ],
    ];
}
```

Catatan:

Validasi Form Request tidak menggantikan validasi business logic di dalam Service. Kondisi stok harus diperiksa kembali saat transaksi dijalankan karena data bisa berubah setelah validasi awal.

## 6.4. Queue Jobs

**Rule: Gunakan Laravel Queue untuk proses asynchronous yang tidak harus diselesaikan dalam HTTP request utama.**

Queue digunakan ketika terdapat proses yang berpotensi memerlukan waktu lama atau tidak harus langsung selesai sebelum response diberikan.

Contoh penggunaan:

- Mengirim notifikasi setelah transaksi berhasil.
- Membuat export laporan stok berukuran besar.
- Memproses laporan dalam jumlah data besar.
- Mengirim email konfirmasi transaksi jika nantinya diperlukan.

Struktur:

```text
app/
└── Jobs/
    ├── SendPurchaseOrderNotification.php
    ├── SendSalesOrderNotification.php
    └── ExportStockReport.php
```

Ketentuan:

1. Proses asynchronous harus menggunakan Queue Job.
2. Job harus memiliki mekanisme retry yang sesuai.
3. Job yang gagal harus dapat ditangani melalui failed jobs.
4. Job yang berpotensi dijalankan ulang harus dirancang idempotent.
5. Jangan menjalankan proses kritis perubahan stok secara asynchronous tanpa mekanisme konsistensi yang jelas.

**Penting:** Pencatatan unit, perubahan status SN, dan stock movement harus berada dalam satu transaksi database yang konsisten. Jangan memisahkan ketiganya ke job asynchronous yang dapat menyebabkan stok dan histori tidak sinkron.

## 6.5. Events & Listeners

**Rule: Gunakan Event dan Listener apabila terdapat proses lanjutan yang perlu dipisahkan dari proses utama.**

Event digunakan untuk menandakan suatu kejadian bisnis telah berlangsung.

Contoh event:

```text
app/
└── Events/
    ├── PurchaseOrderReceived.php
    └── SalesOrderCompleted.php
```

Contoh listener:

```text
app/
└── Listeners/
    ├── SendPurchaseNotification.php
    └── SendSalesNotification.php
```

Contoh alur:

```text
PurchaseOrderService
        |
        v
Purchase Order berhasil disimpan
        |
        v
PurchaseOrderReceived Event
        |
        v
Listener
        |
        v
Send Notification Job
```

Ketentuan:

1. Event digunakan untuk mengumumkan kejadian bisnis.
2. Listener menangani proses lanjutan yang berkaitan dengan event.
3. Listener boleh menjalankan Queue Job jika prosesnya membutuhkan background processing.
4. Event dan Listener tidak wajib dibuat untuk setiap operasi CRUD.
5. Jangan memindahkan validasi dan proses inti inventory ke listener asynchronous.

Untuk project ini, Event dan Listener bersifat kondisional. Implementasikan jika memang terdapat kebutuhan proses lanjutan, bukan sekadar untuk menambah kompleksitas arsitektur.

---

# 7. Database Integrity & Concurrency Control

## 7.1. Database Transaction

**Rule: Setiap transaksi inventory yang melibatkan beberapa tabel wajib menggunakan DB::transaction().**

Contoh inbound:

1. Membuat Purchase Order.
2. Membuat Purchase Order Items.
3. Membuat Stock Units.
4. Membuat Stock Movements.

Seluruh proses harus berhasil atau seluruhnya dibatalkan.

Contoh outbound:

1. Membuat Sales Order.
2. Membuat Sales Order Items.
3. Mengunci dan memvalidasi unit.
4. Memperbarui status unit.
5. Membuat Stock Movements.

Jika salah satu proses gagal, seluruh transaksi harus di-rollback.

## 7.2. Race Condition Prevention

**Rule: Seluruh operasi pengeluaran stok harus aman terhadap concurrent requests.**

Race condition dapat terjadi ketika dua pengguna mencoba mengeluarkan unit dengan SN yang sama pada waktu bersamaan.

Contoh:

- Admin A mengambil SN LNV001.
- Admin B mengambil SN LNV001.
- Keduanya membaca status `in_stock`.
- Keduanya mencoba mengeluarkan unit tersebut.

Tanpa pengamanan, sistem berisiko mencatat pengeluaran ganda.

Solusi wajib:

1. Gunakan database transaction.
2. Gunakan `lockForUpdate()` ketika mengambil unit yang akan dikeluarkan.
3. Lakukan validasi ulang status unit setelah mendapatkan lock.
4. Pastikan perubahan status dan pencatatan mutasi berada dalam transaksi yang sama.
5. Terapkan unique constraint pada SN di database.

Contoh:

```php
DB::transaction(function () use ($serialNumbers) {

    $units = StockUnit::whereIn(
        'serial_number',
        $serialNumbers
    )
    ->orderBy('id')
    ->lockForUpdate()
    ->get();

    if ($units->count() !== count($serialNumbers)) {
        throw new \RuntimeException('Unit tidak ditemukan.');
    }

    foreach ($units as $unit) {

        if ($unit->status !== 'in_stock') {
            throw new \RuntimeException(
                "SN {$unit->serial_number} tidak tersedia."
            );
        }

        $unit->update([
            'status' => 'sold',
        ]);

        // Catat stock movement OUT
    }

}, 3);
```

Angka `3` merupakan jumlah percobaan ulang transaksi ketika terjadi deadlock.

Ketentuan tambahan:

- Kunci record dalam urutan konsisten untuk mengurangi risiko deadlock.
- Jangan menahan database lock selama proses pengiriman email atau proses eksternal.
- Tangani kegagalan lock dan deadlock secara tepat.
- Jangan mengandalkan validasi frontend untuk mencegah race condition.

## 7.3. Historical Stock Integrity

**Rule: Sistem harus mampu menghitung stok berdasarkan histori transaksi, bukan hanya status stok saat ini.**

Contoh:

- Barang masuk 5 November.
- Barang keluar 7 November.
- Admin membuat laporan periode 1–10 November.

Sistem harus menggunakan tanggal transaksi untuk menghitung mutasi.

Jika terdapat transaksi keluar pada tanggal sebelum barang masuk, sistem harus menolak transaksi tersebut berdasarkan ketersediaan stok pada tanggal transaksi.

Untuk itu, validasi transaksi bertanggal mundur harus mempertimbangkan histori pergerakan hingga tanggal yang diminta.

Histori stock movement harus bersifat append-only untuk transaksi normal. Koreksi dilakukan melalui transaksi koreksi yang tercatat, bukan mengubah histori lama secara sembarangan.

---

# 8. Database Performance Optimization

## 8.1. Database Indexing

**Rule: Kolom yang sering digunakan dalam pencarian, filtering, sorting, dan relasi harus dievaluasi untuk diberikan index.**

Index yang disarankan:

| Tabel | Kolom | Jenis |
|---|---|---|
| products | sku | Unique |
| purchase_orders | po_number | Unique |
| purchase_orders | transaction_date | Index |
| sales_orders | order_number | Unique |
| sales_orders | transaction_date | Index |
| stock_units | serial_number | Unique |
| stock_units | product_id, status | Composite |
| stock_movements | stock_unit_id | Index |
| stock_movements | transaction_date, movement_type | Composite |

Contoh migration:

```php
$table->unique('serial_number');

$table->index(
    ['product_id', 'status']
);

$table->index(
    ['transaction_date', 'movement_type']
);
```

Ketentuan:

1. Semua primary key harus menggunakan index bawaan.
2. Unique constraint harus diterapkan pada identitas unik seperti SKU, nomor PO, nomor pesanan, dan SN.
3. Foreign key harus memiliki index yang sesuai.
4. Gunakan composite index ketika query sering memfilter beberapa kolom secara bersamaan.
5. Hindari index berlebihan karena dapat memperlambat INSERT dan UPDATE.
6. Evaluasi index menggunakan `EXPLAIN` pada query yang penting.

Index harus disesuaikan dengan pola query aktual, bukan dibuat secara sembarangan.

## 8.2. Pagination

**Rule: Semua halaman yang menampilkan koleksi data berpotensi besar wajib menggunakan pagination.**

Halaman yang wajib mempertimbangkan pagination:

- Daftar produk.
- Daftar Purchase Order.
- Daftar Sales Order.
- Daftar Serial Number.
- Histori stock movement.
- Laporan mutasi stok jika jumlah produk besar.

Contoh:

```php
$products = Product::query()
    ->orderBy('id', 'desc')
    ->paginate(15);
```

Ketentuan:

- Default pagination adalah 15 data per halaman.
- Filter dan pencarian harus tetap terbawa ketika berpindah halaman.
- Jangan mengambil seluruh histori transaksi sekaligus.
- Gunakan `cursorPaginate()` jika dibutuhkan untuk dataset besar dengan navigasi berurutan.

## 8.3. N+1 Query Prevention

**Rule: Gunakan Eloquent Eager Loading untuk menghindari N+1 query problem.**

Contoh query yang tidak efisien:

```php
$orders = PurchaseOrder::all();

foreach ($orders as $order) {
    echo $order->items->count();
}
```

Masalahnya adalah setiap order dapat memicu query tambahan untuk mengambil items.

Solusi:

```php
$orders = PurchaseOrder::with('items.product')
    ->latest()
    ->paginate(15);
```

Ketentuan:

1. Gunakan `with()` untuk relasi yang diperlukan.
2. Gunakan `withCount()` untuk menghitung jumlah relasi.
3. Hindari query berulang di dalam foreach.
4. Hindari mengambil relasi yang tidak digunakan.
5. Gunakan Laravel Debugbar atau query logging saat development untuk mendeteksi N+1.

## 8.4. Query Optimization

**Rule: Query database harus efisien dan hanya mengambil data yang diperlukan.**

Ketentuan:

- Hindari penggunaan `SELECT *` jika hanya beberapa kolom yang dibutuhkan.
- Gunakan `select()` untuk membatasi kolom.
- Gunakan agregasi database untuk perhitungan total.
- Hindari query berulang untuk perhitungan laporan.
- Gunakan JOIN atau conditional aggregation untuk laporan mutasi.
- Hindari melakukan filtering data besar di PHP jika dapat dilakukan langsung di database.
- Gunakan `EXPLAIN` untuk memeriksa query yang lambat.

Contoh:

```php
$products = Product::query()
    ->select('id', 'sku', 'name')
    ->withCount([
        'stockUnits as available_stock' => function ($query) {
            $query->where('status', 'in_stock');
        }
    ])
    ->paginate(15);
```

## 8.5. Stock Report Aggregation

**Rule: Laporan mutasi stok harus menggunakan agregasi database dan tidak boleh melakukan query terpisah berulang kali untuk setiap produk jika dapat dioptimalkan.**

Perhitungan laporan harus menghasilkan:

- Opening stock.
- Total inbound.
- Total outbound.
- Closing stock.

Gunakan conditional aggregation seperti:

```sql
SUM(
    CASE
        WHEN movement_type = 'in'
        THEN 1
        ELSE 0
    END
)
```

Gunakan kondisi tanggal yang tepat untuk membedakan transaksi sebelum periode dan transaksi dalam periode.

---

# 9. Application Architecture

## 9.1. Folder Structure

```text
app/
├── Events/
│   ├── PurchaseOrderReceived.php
│   └── SalesOrderCompleted.php
│
├── Http/
│   ├── Controllers/
│   │   ├── ProductController.php
│   │   ├── PurchaseOrderController.php
│   │   ├── SalesOrderController.php
│   │   └── StockReportController.php
│   │
│   └── Requests/
│       ├── StorePurchaseOrderRequest.php
│       ├── StoreSalesOrderRequest.php
│       └── StockReportRequest.php
│
├── Jobs/
│   ├── SendPurchaseOrderNotification.php
│   ├── SendSalesOrderNotification.php
│   └── ExportStockReport.php
│
├── Listeners/
│   ├── SendPurchaseNotification.php
│   └── SendSalesNotification.php
│
├── Models/
│   ├── Product.php
│   ├── PurchaseOrder.php
│   ├── PurchaseOrderItem.php
│   ├── StockUnit.php
│   ├── SalesOrder.php
│   ├── SalesOrderItem.php
│   └── StockMovement.php
│
└── Services/
    ├── PurchaseOrderService.php
    ├── SalesOrderService.php
    └── StockReportService.php
```

Event, Listener, dan Job dibuat hanya ketika ada kebutuhan implementasi yang sesuai.

## 9.2. Request Flow

```text
User
 |
 v
Route
 |
 v
Controller
 |
 v
Form Request
 |
 v
Service
 |
 v
Model / Database
 |
 v
Response
```

Untuk proses lanjutan:

```text
Service
 |
 v
Business Event
 |
 v
Listener
 |
 v
Queue Job
 |
 v
Background Processing
```

---

# 10. Non-Functional Requirements

| ID | Requirement |
|---|---|
| NFR-01 | Sistem harus menjaga konsistensi data inventory. |
| NFR-02 | SN wajib memiliki unique constraint. |
| NFR-03 | Transaksi multi-tabel harus atomic. |
| NFR-04 | Sistem harus mencegah race condition saat pengeluaran barang. |
| NFR-05 | Query harus dioptimalkan menggunakan index yang sesuai. |
| NFR-06 | Data list harus menggunakan pagination. |
| NFR-07 | Sistem harus mencegah N+1 query. |
| NFR-08 | Business logic harus dipisahkan dari controller. |
| NFR-09 | Validasi HTTP harus menggunakan Form Request. |
| NFR-10 | Proses asynchronous menggunakan Queue jika diperlukan. |
| NFR-11 | Event dan Listener digunakan jika terdapat kebutuhan event-driven processing. |
| NFR-12 | Histori mutasi harus dapat dipertanggungjawabkan. |
| NFR-13 | Sistem harus mendukung tanggal transaksi manual. |
| NFR-14 | Kegagalan transaksi harus dapat ditangani dengan rollback. |

---

# 11. User Flow

## 11.1. Inbound Flow

1. Admin membuka halaman Purchase Order.
2. Admin membuat transaksi.
3. Admin memasukkan nomor PO.
4. Admin menentukan tanggal transaksi.
5. Admin memilih produk dan quantity.
6. Admin memasukkan SN.
7. Form Request memvalidasi input.
8. Service memproses penerimaan.
9. Database menyimpan PO, unit, dan stock movement dalam satu transaksi.
10. Sistem menampilkan hasil transaksi.

## 11.2. Outbound Flow

1. Admin membuka halaman Sales Order.
2. Admin membuat pesanan.
3. Admin menentukan tanggal transaksi.
4. Admin memilih produk dan quantity.
5. Admin memasukkan SN.
6. Form Request memvalidasi format input.
7. Service mengunci dan memeriksa unit.
8. Sistem memvalidasi ketersediaan stok berdasarkan tanggal transaksi.
9. Sistem memperbarui status unit.
10. Sistem mencatat stock movement.
11. Sistem menampilkan hasil transaksi.

## 11.3. Stock Report Flow

1. Admin membuka halaman laporan.
2. Admin menentukan tanggal awal dan akhir.
3. Form Request memvalidasi periode.
4. Service menjalankan query agregasi.
5. Database mengembalikan hasil perhitungan.
6. Sistem menampilkan laporan.

---

# 12. Acceptance Testing Scenario

| ID | Skenario | Expected Result |
|---|---|---|
| TC-01 | Membuat produk | Berhasil |
| TC-02 | Membuat PO | Berhasil |
| TC-03 | Input tanggal transaksi manual | Berhasil |
| TC-04 | Mendaftarkan SN | Berhasil |
| TC-05 | SN duplikat | Ditolak |
| TC-06 | Quantity tidak sesuai SN | Ditolak |
| TC-07 | Membuat Sales Order | Berhasil |
| TC-08 | Mengeluarkan SN tersedia | Berhasil |
| TC-09 | Mengeluarkan SN tidak terdaftar | Ditolak |
| TC-10 | Mengeluarkan SN yang sudah sold | Ditolak |
| TC-11 | Mengeluarkan SN produk berbeda | Ditolak |
| TC-12 | Dua request mengeluarkan SN sama | Hanya satu berhasil |
| TC-13 | Kegagalan di tengah transaksi | Seluruh transaksi rollback |
| TC-14 | Melihat stok terkini | Sesuai status unit |
| TC-15 | Filter mutasi stok | Sesuai periode |
| TC-16 | Menghitung saldo awal | Sesuai histori |
| TC-17 | Menghitung saldo akhir | Sesuai rumus |
| TC-18 | Melakukan transaksi bertanggal mundur | Sesuai ketersediaan historis |

---

# 13. Development Roadmap

| Tahap | Aktivitas |
|---|---|
| 1 | Setup Laravel dan MySQL |
| 2 | Database design dan migration |
| 3 | Model dan Eloquent relationships |
| 4 | Product CRUD |
| 5 | Form Request dan Service Layer |
| 6 | Purchase Order dan Inbound |
| 7 | Sales Order dan Outbound |
| 8 | Concurrency control dan data integrity |
| 9 | Stock monitoring |
| 10 | Stock report dan query aggregation |
| 11 | Database optimization |
| 12 | Testing dan final review |

---

# 14. Definition of Done

Project dinyatakan selesai apabila:

- [ ] Seluruh GOAL 1, GOAL 2, dan GOAL 3 berhasil diimplementasikan.
- [ ] Setiap unit barang memiliki SN unik.
- [ ] Transaksi inbound mendukung tanggal manual.
- [ ] Transaksi outbound mendukung tanggal manual.
- [ ] Validasi SN berjalan dengan benar.
- [ ] Stok terkini dapat ditampilkan.
- [ ] Mutasi stok dapat dihitung berdasarkan periode.
- [ ] Saldo awal dan akhir akurat.
- [ ] Controller tidak mengandung business logic.
- [ ] Form Request digunakan untuk validasi HTTP.
- [ ] Business logic ditempatkan di Service Layer.
- [ ] Transaksi inventory menggunakan database transaction.
- [ ] Race condition ditangani.
- [ ] Index database diterapkan sesuai kebutuhan query.
- [ ] Pagination diterapkan pada halaman data.
- [ ] N+1 query dihindari.
- [ ] Queue, Event, dan Listener digunakan sesuai kebutuhan.
- [ ] Seluruh skenario pengujian berhasil.

---

# 15. Project Success Criteria

Project dinyatakan berhasil apabila sistem mampu:

1. Melacak setiap unit barang berdasarkan Serial Number.
2. Mencatat barang masuk dan keluar secara akurat.
3. Mencegah pengeluaran barang yang tidak tersedia.
4. Menghindari transaksi ganda akibat concurrent requests.
5. Menampilkan stok terkini.
6. Menghasilkan laporan mutasi stok yang akurat.
7. Memiliki struktur backend yang maintainable.
8. Memiliki query database yang efisien dan terukur.

**Final Objective:**

Membangun Mini Inventory & Order Management System yang tidak hanya berfungsi untuk mencatat transaksi inventory, tetapi juga memiliki integritas data, kontrol concurrency, dan arsitektur backend yang terstruktur sehingga setiap unit barang dapat dilacak dan dipertanggungjawabkan dari penerimaan hingga pengeluaran gudang.