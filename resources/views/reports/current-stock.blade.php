@extends('layouts.app')

@section('title', 'Cek Stok Terkini')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900">Cek Stok Terkini</h1>
        <p class="text-slate-500 text-xs sm:text-sm mt-0.5">Menampilkan jumlah stok tersedia saat ini (<code class="font-mono">in_stock</code>) beserta daftar Serial Number siap jual.</p>
    </div>

    <form action="{{ route('reports.current-stock') }}" method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari SKU atau nama produk..."
            class="bg-white border border-slate-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow-2xs transition">
            Cari
        </button>
        @if (request('search'))
            <a href="{{ route('reports.current-stock') }}" class="bg-white border border-slate-300 text-slate-600 hover:bg-slate-50 px-3.5 py-2 rounded-lg">Reset</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                    <th class="py-3.5 px-4">SKU</th>
                    <th class="py-3.5 px-4">Nama Produk</th>
                    <th class="py-3.5 px-4 text-right">Stok Tersedia (in_stock)</th>
                    <th class="py-3.5 px-4 text-right">Total Terjual (All-Time)</th>
                    <th class="py-3.5 px-4">Daftar Serial Number Tersedia (Siap Dikeluarkan)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($products as $product)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-4 px-4 font-mono font-bold text-slate-900 align-top whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded bg-slate-100 border border-slate-200 text-xs">{{ $product->sku }}</span>
                        </td>
                        <td class="py-4 px-4 font-semibold text-slate-800 align-top">{{ $product->name }}</td>
                        <td class="py-4 px-4 text-right align-top whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $product->available_stock > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                {{ $product->available_stock }} unit
                            </span>
                        </td>
                        <td class="py-4 px-4 text-right align-top whitespace-nowrap font-mono text-slate-500">
                            {{ $product->sold_stock }} unit
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex flex-wrap gap-1.5">
                                @forelse ($product->stockUnits as $unit)
                                    <span class="px-2 py-0.5 rounded font-mono text-xs bg-white text-slate-800 border border-slate-300">
                                        {{ $unit->serial_number }}
                                    </span>
                                @empty
                                    <span class="text-xs text-slate-400 italic">Stok habis — tidak ada SN tersedia</span>
                                @endforelse
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center text-slate-400">Belum ada data produk.</td>
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
@endsection
