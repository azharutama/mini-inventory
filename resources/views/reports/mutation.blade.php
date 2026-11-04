@extends('layouts.app')

@section('title', 'Laporan Mutasi Stok per Periode')

@section('content')
<!-- Header & Filter Periode -->
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900">Laporan Mutasi Stok per Periode</h1>
        <p class="text-slate-500 text-xs sm:text-sm mt-0.5">
            Perhitungan mutasi berbasis tanggal transaksi (<code class="font-mono">transaction_date</code>) dengan rumus:
            <strong class="text-slate-700">Saldo Akhir = Saldo Awal + Masuk − Keluar</strong>.
        </p>
    </div>

    <form action="{{ route('reports.mutation') }}" method="GET" class="bg-white p-3 rounded-xl border border-slate-200 shadow-2xs flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Tanggal Awal</label>
            <input type="date" name="start_date" value="{{ $startDate }}" required
                class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
        </div>
        <div>
            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Tanggal Akhir</label>
            <input type="date" name="end_date" value="{{ $endDate }}" required
                class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-1.5 rounded-lg shadow-2xs transition h-[34px]">
            Terapkan Filter
        </button>
    </form>
</div>

<!-- Tabel Detail Mutasi Stok per Produk -->
<div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="p-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
        <div class="font-bold text-slate-800">Rincian Mutasi per Produk</div>
        <div class="flex flex-wrap gap-2 text-xs">
            <a href="{{ route('reports.mutation', ['start_date' => '2026-10-01', 'end_date' => '2026-10-31']) }}"
                class="px-2.5 py-1 rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50">
                Periode Oktober 2026
            </a>
            <a href="{{ route('reports.mutation', ['start_date' => '2026-11-01', 'end_date' => '2026-11-30']) }}"
                class="px-2.5 py-1 rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50">
                Periode November 2026
            </a>
            <a href="{{ route('reports.mutation', ['start_date' => '2026-10-01', 'end_date' => '2026-11-30']) }}"
                class="px-2.5 py-1 rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50">
                Gabungan Okt–Nov 2026
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                    <th class="py-3.5 px-4">SKU</th>
                    <th class="py-3.5 px-4">Nama Produk</th>
                    <th class="py-3.5 px-4 text-right">Saldo Awal</th>
                    <th class="py-3.5 px-4 text-right">Barang Masuk (In)</th>
                    <th class="py-3.5 px-4 text-right">Barang Keluar (Out)</th>
                    <th class="py-3.5 px-4 text-right">Saldo Akhir</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($mutations as $row)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                            <span class="px-2 py-1 rounded bg-slate-100 border border-slate-200 text-xs">{{ $row->sku }}</span>
                        </td>
                        <td class="py-3.5 px-4 font-medium text-slate-800">{{ $row->name }}</td>
                        <td class="py-3.5 px-4 text-right font-mono text-slate-600">{{ $row->opening_stock }}</td>
                        <td class="py-3.5 px-4 text-right font-mono font-semibold text-emerald-600">+{{ $row->total_inbound }}</td>
                        <td class="py-3.5 px-4 text-right font-mono font-semibold text-amber-600">-{{ $row->total_outbound }}</td>
                        <td class="py-3.5 px-4 text-right">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md font-mono font-bold text-xs bg-blue-50 text-blue-700 border border-blue-200">
                                {{ $row->closing_stock }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-400">Belum ada data produk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($mutations->hasPages())
        <div class="p-4 border-t border-slate-100 bg-slate-50/40">
            {{ $mutations->links() }}
        </div>
    @endif
</div>
@endsection
