@extends('layouts.app')

@section('title', 'Purchase Order (Inbound)')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900">Purchase Order & Penerimaan Barang (Inbound)</h1>
        <p class="text-slate-500 text-xs sm:text-sm mt-0.5">Catat pembelian barang masuk beserta Serial Number (SN) unik untuk setiap unit fisik.</p>
    </div>
    <div class="flex items-center gap-2 text-xs bg-blue-50 text-blue-800 border border-blue-200 px-3.5 py-2 rounded-xl">
        <span class="font-semibold">Tips:</span>
        <span>Ketik 1 Serial Number per baris, jumlah baris otomatis dihitung di bawah kolom SN.</span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    <!-- Form Buat Purchase Order & Input SN -->
    <div class="lg:col-span-5 bg-white p-6 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
                <h2 class="text-base font-bold text-slate-900">Form Barang Masuk (PO)</h2>
                <p class="text-xs text-slate-500">Status unit baru otomatis tercatat <code class="font-mono text-emerald-700">in_stock</code>.</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">INBOUND</span>
        </div>

        <form action="{{ route('purchase-orders.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Nomor PO *</label>
                    <input type="text" name="po_number" value="{{ old('po_number', 'PO-' . now()->format('Ymd-His')) }}" required
                        class="w-full border border-slate-300 rounded-lg px-3.5 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Tanggal Transaksi *</label>
                    <input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" required
                        class="w-full border border-slate-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                </div>
            </div>

            <div id="po-items-container" class="space-y-3.5">
                @php
                    $oldItems = old('items', [['product_id' => '', 'quantity' => 1, 'serial_numbers' => []]]);
                @endphp
                @foreach ($oldItems as $idx => $item)
                    @php
                        $snText = is_array($item['serial_numbers'] ?? null)
                            ? implode("\n", $item['serial_numbers'])
                            : (string) ($item['serial_numbers'] ?? '');
                    @endphp
                    <div class="po-item-card p-4 rounded-xl border border-slate-200 bg-slate-50/70 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-xs uppercase tracking-wider text-slate-500">Item Barang Masuk</span>
                            <button type="button" class="remove-item text-xs font-medium text-rose-600 hover:text-rose-800 hover:underline">
                                Hapus Item
                            </button>
                        </div>

                        <div class="grid grid-cols-3 gap-2.5">
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-slate-700 mb-1">Pilih Produk *</label>
                                <select name="items[{{ $idx }}][product_id]" required
                                    class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                    <option value="">-- Pilih Produk --</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" @selected(($item['product_id'] ?? '') == $product->id)>
                                            [{{ $product->sku }}] {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Quantity *</label>
                                <input type="number" name="items[{{ $idx }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" min="1" required
                                    class="qty-input w-full border border-slate-300 rounded-lg px-3 py-2 bg-white text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-medium text-slate-700">Daftar Serial Number (1 SN per baris) *</label>
                                <span class="sn-badge text-[11px] font-medium px-2 py-0.5 rounded bg-slate-200 text-slate-700">0 SN terinput</span>
                            </div>
                            <textarea name="items[{{ $idx }}][serial_numbers]" rows="3" required
                                placeholder="Ketik 1 Serial Number per baris, misal:&#10;LNV009&#10;LNV010"
                                class="sn-textarea w-full border border-slate-300 rounded-lg px-3 py-2 font-mono text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">{{ $snText }}</textarea>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-wrap justify-between items-center gap-3 pt-2">
                <button type="button" id="add-po-item" class="text-xs font-semibold px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 transition">
                    + Tambah Baris Produk
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-5 rounded-lg shadow-xs transition">
                    Simpan PO & Terima Barang
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Riwayat Purchase Order -->
    <div class="lg:col-span-7 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Riwayat Purchase Order (Inbound)</h2>
                <p class="text-xs text-slate-500">Daftar transaksi penerimaan barang beserta status terkini tiap Serial Number.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4">Nomor PO</th>
                        <th class="py-3 px-4">Tanggal Masuk</th>
                        <th class="py-3 px-4">Detail Produk & Serial Number</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($purchaseOrders as $po)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-4 px-4 font-mono font-bold text-slate-900 align-top whitespace-nowrap">
                                {{ $po->po_number }}
                            </td>
                            <td class="py-4 px-4 align-top whitespace-nowrap text-slate-600 font-medium">
                                {{ $po->transaction_date->format('Y-m-d') }}
                            </td>
                            <td class="py-4 px-4 space-y-3">
                                @foreach ($po->items as $item)
                                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80 text-xs">
                                        <div class="flex items-center justify-between font-semibold text-slate-800 mb-1.5">
                                            <span><span class="font-mono text-slate-900">[{{ $item->product->sku }}]</span> {{ $item->product->name }}</span>
                                            <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-700">{{ $item->quantity }} unit</span>
                                        </div>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($item->stockUnits as $unit)
                                                <span class="px-2 py-0.5 rounded font-mono text-[11px] bg-white border border-slate-300 text-slate-800">
                                                    {{ $unit->serial_number }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-10 text-center text-slate-400">Belum ada transaksi Purchase Order.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($purchaseOrders->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/40">
                {{ $purchaseOrders->links() }}
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let idx = {{ count($oldItems) }};
    const container = document.getElementById('po-items-container');

    function updateSnCount(card) {
        const textarea = card.querySelector('.sn-textarea');
        const badge = card.querySelector('.sn-badge');
        const qtyInput = card.querySelector('.qty-input');
        if (!textarea || !badge) return;

        const lines = textarea.value.split(/[\r\n,]+/).map(s => s.trim()).filter(Boolean);
        const qty = parseInt(qtyInput?.value || '0', 10);
        badge.textContent = `${lines.length} / ${qty} SN`;

        if (lines.length === qty && qty > 0) {
            badge.className = 'sn-badge text-[11px] font-semibold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800';
        } else {
            badge.className = 'sn-badge text-[11px] font-medium px-2 py-0.5 rounded bg-amber-100 text-amber-800';
        }
    }

    container.querySelectorAll('.po-item-card').forEach(updateSnCount);

    container.addEventListener('input', function (e) {
        const card = e.target.closest('.po-item-card');
        if (card) updateSnCount(card);
    });

    document.getElementById('add-po-item').addEventListener('click', function () {
        const firstCard = container.querySelector('.po-item-card');
        const clone = firstCard.cloneNode(true);
        clone.querySelectorAll('select, input, textarea').forEach(el => {
            el.name = el.name.replace(/items\[\d+\]/, `items[${idx}]`);
            if (el.tagName === 'INPUT') el.value = 1;
            else el.value = '';
        });
        container.appendChild(clone);
        updateSnCount(clone);
        idx++;
    });

    container.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-item')) {
            if (container.querySelectorAll('.po-item-card').length > 1) {
                e.target.closest('.po-item-card').remove();
            }
        }
    });
});
</script>
@endsection
