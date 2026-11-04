# Product Requirements Document (PRD)
## Mini Inventory & Order Management System (Serial Number Tracking)

| Item | Keterangan |
|------|------------|
| Versi | 2.0 (disederhanakan agar mudah dijelaskan) |
| Tanggal | 4 November 2026 |
| Stack | Laravel + Blade + MySQL (InnoDB) |

---

## 1. Overview

Sistem inventory dan penjualan sederhana untuk perusahaan distribusi yang melacak barang **sampai ke nomor seri (SN) fisik**. Setiap barang yang masuk dan keluar gudang harus bisa dipertanggungjawabkan.

Sistem hanya punya **3 fitur utama**:

1. **Barang Masuk:** buat Purchase Order (PO), lalu terima barang dengan mendaftarkan SN.
2. **Barang Keluar:** buat Sales Order (SO), lalu keluarkan barang dengan memasukkan SN.
3. **Laporan:** cek stok terkini dan mutasi stok per periode.

Fokus penilaian: **alur data dan logika backend**. Tampilan cukup sederhana.

## 2. Ide Inti (untuk menjelaskan ke penguji)

> **Satu unit fisik = satu baris di tabel `product_units`.**
> Baris itu menyimpan SN, tanggal masuk, dan tanggal keluar.
> Semua laporan stok dihitung dari tabel ini saja.

```
 PO (pesan barang)                         SO (pesanan pelanggan)
      │                                          │
      ▼  terima + input SN + pilih tanggal       ▼  keluarkan + input SN + pilih tanggal
 ┌───────────────────────────────────────────────────────────┐
 │ product_units:  SN | product | received_date | issued_date │
 └───────────────────────────────────────────────────────────┘
                              │
                              ▼
               Cek Stok  &  Mutasi Stok (SQL)
```

- Unit **tersedia** = `issued_date IS NULL`.
- Tidak perlu tabel stok atau ledger terpisah, sehingga tidak ada angka stok yang bisa "tidak sinkron".

## 3. Aturan Bisnis

| ID | Aturan |
|----|--------|
| BR-01 | 1 unit fisik = 1 SN, dan **SN unik** di seluruh sistem. |
| BR-02 | Setiap transaksi (PO, penerimaan, SO, pengeluaran) punya **input tanggal yang dipilih manual**. |
| BR-03 | Jumlah SN yang diinput saat penerimaan = qty yang diterima, dan tidak melebihi sisa qty PO. |
| BR-04 | Pengeluaran hanya boleh untuk SN yang **ada dan belum keluar**. |
| BR-05 | SN yang dikeluarkan harus **produk yang sama** dengan item di SO, dan tidak melebihi sisa qty SO. |
| BR-06 | Tanggal keluar sebuah SN **tidak boleh lebih awal** dari tanggal masuknya. |

## 4. Fitur

### 4.1 Master Produk
CRUD sederhana: `kode`, `nama`. (Supplier dan pelanggan cukup berupa **teks nama** di PO/SO, tanpa master terpisah.)

### 4.2 Barang Masuk (Goal 1)
1. Buat **PO**: no. PO, nama supplier, **tanggal PO**, daftar item (produk + qty).
2. Buka detail PO, klik **Terima Barang**: pilih **tanggal terima**, isi **SN per unit** (textarea, satu SN per baris) untuk tiap item.
3. Validasi: SN tidak duplikat, jumlah SN sesuai qty, tidak melebihi sisa PO.
4. Simpan: setiap SN menjadi 1 baris `product_units` dengan `received_date` yang dipilih.

### 4.3 Barang Keluar (Goal 2)
1. Buat **SO**: no. SO, nama pelanggan, **tanggal SO**, daftar item (produk + qty).
2. Buka detail SO, klik **Keluarkan Barang**: pilih **tanggal keluar**, isi **SN** unit yang dikeluarkan.
3. Validasi: SN ada, belum keluar, produk sesuai, tidak melebihi sisa SO, tanggal keluar >= tanggal masuk SN.
4. Simpan: kolom `issued_date` dan `sales_order_id` pada unit diisi.

### 4.4 Laporan (Goal 3)

**Cek Stok Terkini:** jumlah unit dengan `issued_date IS NULL` per produk.

**Mutasi Stok per Periode** (filter Tanggal Awal s/d Tanggal Akhir, inklusif):

| Kolom | Rumus |
|-------|-------|
| Saldo Awal | unit masuk **sebelum** Tanggal Awal dan belum keluar sebelum Tanggal Awal |
| Total Masuk | unit dengan `received_date` di dalam rentang |
| Total Keluar | unit dengan `issued_date` di dalam rentang |
| Saldo Akhir | Saldo Awal + Total Masuk - Total Keluar |

Perhitungan memakai **tanggal transaksi yang dipilih**, bukan waktu input, sehingga simulasi lintas tanggal tetap akurat.

## 5. Database (6 Tabel)

| Tabel | Kolom utama |
|-------|-------------|
| `products` | `id`, `code` (UNIQUE), `name` |
| `purchase_orders` | `id`, `po_number` (UNIQUE), `supplier_name`, `po_date` |
| `purchase_order_items` | `id`, `purchase_order_id`, `product_id`, `qty` |
| `sales_orders` | `id`, `so_number` (UNIQUE), `customer_name`, `so_date` |
| `sales_order_items` | `id`, `sales_order_id`, `product_id`, `qty` |
| `product_units` | `id`, **`serial_number` (UNIQUE)**, `product_id`, `purchase_order_id`, `received_date`, `sales_order_id` (NULL), `issued_date` (NULL) |

Relasi:

```
products ─< purchase_order_items >─ purchase_orders
products ─< sales_order_items    >─ sales_orders
products ─< product_units >─ purchase_orders   (asal masuk)
                         >─ sales_orders       (tujuan keluar, boleh NULL)
```

Index (secukupnya):

| Index | Fungsi |
|-------|--------|
| `UNIQUE (serial_number)` | Menjamin SN unik dan pencarian SN cepat |
| `(product_id, received_date)` | Saldo awal dan total masuk |
| `(product_id, issued_date)` | Total keluar dan stok terkini |
| FK lain (`purchase_order_id`, `sales_order_id`) | Join, otomatis dibuat oleh `foreignId()->constrained()` |

Kolom tanggal bertipe `DATE`.

**Query mutasi (inti laporan):**

```sql
SELECT p.code, p.name,
  SUM(u.received_date < :start AND (u.issued_date IS NULL OR u.issued_date >= :start)) AS saldo_awal,
  SUM(u.received_date BETWEEN :start AND :end) AS total_masuk,
  SUM(u.issued_date   BETWEEN :start AND :end) AS total_keluar
FROM products p
LEFT JOIN product_units u ON u.product_id = p.id
GROUP BY p.id, p.code, p.name;
-- saldo_akhir = saldo_awal + total_masuk - total_keluar
```

## 6. Arsitektur Laravel (Sederhana & Rapi)

Prinsip: **Controller tipis, logika di Service, validasi di Form Request.**

```
Request ─► FormRequest (validasi format) ─► Controller ─► Service (logika + transaksi) ─► Model/DB
                                                 │
                                                 └─► Blade (hanya tampilan)
```

| Layer | File | Tugas |
|-------|------|-------|
| Form Request | `StorePurchaseOrderRequest`, `ReceiveGoodsRequest`, `StoreSalesOrderRequest`, `IssueGoodsRequest`, `StockMutationRequest` | Validasi **format**: wajib isi, tipe tanggal, SN tidak kosong dan tidak kembar dalam satu input, `end_date >= start_date` |
| Controller | `PurchaseOrderController`, `SalesOrderController`, `StockReportController`, `ProductController` | Terima request, panggil service, return view/redirect. **Tanpa logika bisnis.** |
| Service | `GoodsReceiptService`, `GoodsIssueService`, `StockReportService` | Aturan BR-03 s.d. BR-06, transaksi DB, penguncian, perhitungan laporan |
| Model | `Product`, `PurchaseOrder`, `SalesOrder`, `ProductUnit` | Relasi dan scope, mis. `scopeAvailable()` |
| Blade | `resources/views/...` | Tampilan saja, tanpa query |

Aturan ringkas:
- Controller method maksimal sekitar 10-15 baris.
- Service menerima **array data tervalidasi**, bukan objek `Request`.
- Pelanggaran aturan bisnis dilempar sebagai exception (mis. `DomainException` dengan pesan jelas) lalu ditampilkan sebagai pesan error di Blade.
- Event/Listener dan Queue **tidak dipakai** pada versi ini karena proses cukup ringan dan harus langsung konsisten saat demo. (Bisa ditambahkan nanti untuk audit log atau export laporan besar.)

**Contoh alur service pengeluaran** (mudah dijelaskan, 5 langkah):

```php
public function issue(SalesOrder $so, array $data): void
{
    DB::transaction(function () use ($so, $data) {
        // 1. kunci SO agar tidak diproses ganda bersamaan
        $so = SalesOrder::lockForUpdate()->findOrFail($so->id);

        // 2. ambil & kunci unit berdasarkan daftar SN (1 query whereIn)
        $units = ProductUnit::whereIn('serial_number', $data['serial_numbers'])
                    ->lockForUpdate()->get();

        // 3. validasi: semua SN ada, belum keluar, produk sesuai SO,
        //    tanggal keluar >= tanggal masuk, tidak melebihi sisa qty SO
        // 4. update unit: issued_date, sales_order_id
        // 5. selesai (commit otomatis); jika ada yang gagal, rollback semua
    });
}
```

## 7. Pencegahan Race Condition (Versi Ringkas)

Masalah: dua orang memproses SN atau PO/SO yang sama di waktu bersamaan. Penanganan cukup **3 hal**:

| Risiko | Solusi |
|--------|--------|
| SN yang sama didaftarkan dua kali | `UNIQUE (serial_number)` di database. Jika bentrok, tangkap error dan tampilkan pesan "SN sudah terdaftar". |
| SN yang sama dikeluarkan dua kali | Di dalam transaksi, `lockForUpdate()` pada unit, lalu cek ulang bahwa `issued_date` masih NULL. |
| Qty melebihi PO/SO karena dua proses bersamaan | `lockForUpdate()` pada baris PO/SO di awal transaksi, lalu hitung ulang sisa qty. |

Aturan tambahan:
- Semua operasi tulis dibungkus `DB::transaction()` (gagal satu = batal semua).
- Validasi "SN tersedia / sisa qty" **diulang di dalam service setelah lock**, karena pengecekan di Form Request bisa sudah basi.
- Tombol simpan dinonaktifkan setelah diklik (JS) untuk mencegah submit ganda.
- Transaksi dibuat singkat dan tanpa proses eksternal di dalamnya.

## 8. Performa Database (Versi Ringkas)

| Topik | Aturan |
|-------|--------|
| **Index** | Sesuai tabel di bagian 5 (SN unik, `product_id` + tanggal). |
| **Pagination** | Semua halaman daftar (produk, PO, SO, SN) memakai `paginate(15)->withQueryString()`, dan `{{ $data->links() }}` di Blade. Tidak ada `all()` pada tabel yang bisa besar. |
| **N+1** | Relasi yang dipakai di Blade di-*eager load*: `PurchaseOrder::with('items.product')->paginate()`. Gunakan `withCount()` untuk hitungan. Aktifkan `Model::preventLazyLoading(! app()->isProduction())` agar N+1 langsung ketahuan saat development. |
| **Laporan** | Hitung di SQL (query bagian 5), bukan dengan memuat semua unit lalu dihitung di PHP. |
| **Filter tanggal** | Pakai `whereBetween` pada kolom `DATE`, jangan `whereDate()` agar index terpakai. |
| **Cek SN** | Satu query `whereIn` untuk semua SN, bukan satu query per SN. |

## 9. Halaman

| Halaman | Isi |
|---------|-----|
| Produk | Daftar dan form tambah/ubah |
| Purchase Order | Daftar, form buat PO, detail PO (qty dipesan, diterima, sisa) + tombol Terima Barang |
| Sales Order | Daftar, form buat SO, detail SO (qty dipesan, keluar, sisa) + tombol Keluarkan Barang |
| Cek Stok | Stok terkini per produk |
| Mutasi Stok | Filter tanggal awal/akhir, tabel Saldo Awal, Masuk, Keluar, Saldo Akhir |

## 10. Skenario Demo & Hasil yang Diharapkan

**Transaksi uji (Produk A):**

| # | Transaksi | Tanggal | Detail |
|---|-----------|---------|--------|
| 1 | Terima barang | 2026-01-10 | 5 unit (A001-A005) |
| 2 | Terima barang | 2026-02-05 | 3 unit (A006-A008) |
| 3 | Keluarkan barang | 2026-01-20 | 2 unit (A001, A002) |
| 4 | Keluarkan barang | 2026-02-15 | 4 unit (A003, A004, A006, A007) |

**Laporan mutasi:**

| Periode | Saldo Awal | Masuk | Keluar | Saldo Akhir |
|---------|-----------:|------:|-------:|------------:|
| 01 Jan - 31 Jan 2026 | 0 | 5 | 2 | 3 |
| 01 Feb - 28 Feb 2026 | 3 | 3 | 4 | 2 |
| 01 Jan - 28 Feb 2026 | 0 | 8 | 6 | 2 |
| 15 Jan - 10 Feb 2026 | 5 | 3 | 2 | 6 |

Stok terkini setelah semua transaksi: **2** (A005 dan A008).

**Urutan demo yang disarankan:** buat PO → terima barang (tanggal 10 Jan, input SN) → buat SO → keluarkan barang (input SN) → ulangi untuk transaksi lain → buka Mutasi Stok dan ubah rentang tanggal → tunjukkan angka sesuai tabel di atas.

**Contoh penolakan yang bisa didemokan:** SN kembar saat terima, SN sudah keluar saat dikeluarkan lagi, SN produk lain, tanggal keluar lebih awal dari tanggal masuk, qty melebihi PO/SO.

## 11. Acceptance Criteria

- [ ] SN duplikat ditolak saat penerimaan.
- [ ] SN yang tidak ada, sudah keluar, atau milik produk lain ditolak saat pengeluaran.
- [ ] Tanggal keluar lebih awal dari tanggal masuk ditolak.
- [ ] Qty penerimaan/pengeluaran tidak bisa melebihi sisa PO/SO.
- [ ] Input tanggal tersedia dan berfungsi di PO, penerimaan, SO, dan pengeluaran.
- [ ] Mutasi stok benar untuk berbagai rentang tanggal (sesuai tabel bagian 10) dan Saldo Akhir = Saldo Awal + Masuk - Keluar.
- [ ] Cek stok terkini sama dengan jumlah unit yang belum keluar.
- [ ] Transaksi gagal tidak meninggalkan data setengah tersimpan.
- [ ] Dua pengeluaran bersamaan untuk SN yang sama: hanya satu yang berhasil.
- [ ] Controller tidak berisi logika bisnis; validasi di Form Request; logika di Service.
- [ ] Halaman daftar memakai pagination dan tidak ada N+1.

## 12. Deliverables

1. Source code Laravel + Blade
2. Migration dan seeder (data contoh sesuai bagian 10)
3. `README.md` (versi PHP/Laravel, `php artisan migrate --seed`, `php artisan serve`)
4. Dokumen ini (`PRD.md`)

## 13. Asumsi & Batasan

- Satu gudang, satu satuan stok per produk.
- Penerimaan dan pengeluaran parsial diperbolehkan (satu PO/SO bisa diproses bertahap).
- Tidak ada retur/pembatalan, harga, pajak, atau pembayaran.
- Login/autentikasi opsional (fokus pada logika backend).
- Supplier dan pelanggan disimpan sebagai teks nama (tanpa master data).
