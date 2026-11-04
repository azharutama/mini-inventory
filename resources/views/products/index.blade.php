@extends('layouts.app')

@section('title', 'Master Produk')

@section('content')
<div class="mb-6">
    <h1 class="text-xl font-bold text-slate-900">Master Data Produk</h1>
    <p class="text-slate-500 text-xs sm:text-sm mt-0.5">Daftarkan SKU dan nama produk sebelum melakukan penerimaan atau penjualan barang.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    <!-- Form Tambah Produk -->
    <div class="lg:col-span-4 bg-white p-6 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-900">Tambah Produk Baru</h2>
            <span class="text-xs font-medium px-2 py-0.5 rounded bg-slate-100 text-slate-600">Master</span>
        </div>

        <form action="{{ route('products.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Kode SKU *</label>
                <input type="text" name="sku" value="{{ old('sku') }}" placeholder="Contoh: LNV-X1" required
                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 font-mono uppercase focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                <p class="text-xs text-slate-400 mt-1">Kode unik identitas produk (otomatis huruf kapital).</p>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Nama Produk *</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Lenovo ThinkPad X1 Carbon" required
                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow-xs transition">
                + Simpan Produk
            </button>
        </form>
    </div>

    <!-- Tabel Daftar Produk -->
    <div class="lg:col-span-8 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-slate-900">Daftar Produk Terdaftar</h2>
                <p class="text-xs text-slate-500">Menampilkan ketersediaan unit aktif saat ini untuk setiap SKU.</p>
            </div>
            <form action="{{ route('products.index') }}" method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari SKU atau nama..."
                    class="border border-slate-300 rounded-lg px-3.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium border border-slate-300 px-3.5 py-1.5 rounded-lg transition">
                    Cari
                </button>
                @if (request('search'))
                    <a href="{{ route('products.index') }}" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4">SKU</th>
                        <th class="py-3 px-4">Nama Produk</th>
                        <th class="py-3 px-4 text-right">Stok Tersedia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 font-mono font-semibold text-slate-900">
                                <span class="px-2 py-1 rounded bg-slate-100 border border-slate-200 text-xs">{{ $product->sku }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-800">{{ $product->name }}</td>
                            <td class="py-3.5 px-4 text-right">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $product->available_stock > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $product->available_stock }} unit
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-10 text-center text-slate-400">Belum ada data produk. Silakan tambahkan produk di sebelah kiri.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/40">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
