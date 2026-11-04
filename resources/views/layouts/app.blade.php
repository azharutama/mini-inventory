<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SIMA — Mini Inventory & Order Management')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen font-sans antialiased text-sm flex flex-col">
    <!-- Top Header & Navigation -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between min-h-16 py-2 gap-4">
                <!-- Brand -->
                <a href="{{ route('purchase-orders.index') }}" class="flex items-center gap-2.5 group">
                    <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold shadow-xs group-hover:bg-blue-700 transition">
                        SN
                    </div>
                    <div>
                        <div class="font-bold text-slate-900 text-base leading-none flex items-center gap-2">
                            <span>Mini Inventory</span>
                            <span class="text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded-full">Serial Number</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Sistem Pelacakan Unit & Mutasi Gudang</p>
                    </div>
                </a>

                <!-- Menu Navigasi Berurutan Sesuai Alur Kerja -->
                <nav class="flex flex-wrap items-center gap-1.5">
                    <a href="{{ route('products.index') }}"
                        class="px-3.5 py-2 rounded-lg font-medium text-xs sm:text-sm transition flex items-center gap-1.5 {{ request()->routeIs('products.*') ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <span>Produk</span>
                    </a>
                    <a href="{{ route('purchase-orders.index') }}"
                        class="px-3.5 py-2 rounded-lg font-medium text-xs sm:text-sm transition flex items-center gap-1.5 {{ request()->routeIs('purchase-orders.*') ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <span>Purchase Order (Inbound)</span>
                    </a>
                    <a href="{{ route('sales-orders.index') }}"
                        class="px-3.5 py-2 rounded-lg font-medium text-xs sm:text-sm transition flex items-center gap-1.5 {{ request()->routeIs('sales-orders.*') ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <span>Sales Order (Outbound)</span>
                    </a>
                    <div class="h-5 w-px bg-slate-200 mx-1 hidden sm:block"></div>
                    <a href="{{ route('reports.current-stock') }}"
                        class="px-3.5 py-2 rounded-lg font-medium text-xs sm:text-sm transition flex items-center gap-1.5 {{ request()->routeIs('reports.current-stock') ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <span>Cek Stok</span>
                    </a>
                    <a href="{{ route('reports.mutation') }}"
                        class="px-3.5 py-2 rounded-lg font-medium text-xs sm:text-sm transition flex items-center gap-1.5 {{ request()->routeIs('reports.mutation') ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <span>Mutasi Stok</span>
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Konten Utama -->
    <main class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1">
        @if (session('success'))
            <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-start justify-between gap-3 shadow-2xs">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-600 text-white text-xs font-bold">✓</span>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 flex items-start gap-2.5 shadow-2xs">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-rose-600 text-white text-xs font-bold shrink-0">!</span>
                <div class="font-medium">{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 shadow-2xs">
                <div class="font-semibold flex items-center gap-2 mb-1.5">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-rose-600 text-white text-xs font-bold">!</span>
                    <span>Periksa kembali input Anda:</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-rose-800 ml-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white py-4 mt-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-wrap justify-between items-center text-xs text-slate-400 gap-2">
            <span>Mini Inventory & Order Management System — Pelacakan Unit Berbasis Serial Number</span>
            <span>Alur: <strong>Produk</strong> → <strong>Purchase Order (In)</strong> → <strong>Sales Order (Out)</strong> → <strong>Cek & Mutasi Stok</strong></span>
        </div>
    </footer>

    <script>
        // Cegah double-submit saat tombol simpan diklik ganda di browser
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form').forEach(function (form) {
                form.addEventListener('submit', function () {
                    const btn = form.querySelector('button[type="submit"]');
                    if (btn) {
                        btn.disabled = true;
                        btn.classList.add('opacity-60', 'cursor-not-allowed');
                    }
                });
            });
        });
    </script>
</body>
</html>
