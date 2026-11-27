<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Daftar Transaksi — Seklov</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f7f9fa] font-['Inter'] text-[#202b3d] antialiased">
        <header class="px-4 pt-4 sm:px-6 sm:pt-6">
            <x-navbar detail />
        </header>

        <main class="mx-auto max-w-[1000px] px-4 pb-16 pt-6 sm:px-6 sm:pt-8">
            <nav class="mb-5 text-[13px] text-[#718096]" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-[#00523f]">Beranda</a>
                <span class="px-2">›</span>
                <span class="font-medium text-[#00523f]">Daftar Transaksi</span>
            </nav>

            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="font-['Poppins'] text-[24px] font-bold text-[#202b3d] sm:text-[28px]">
                    Daftar Transaksi COD
                </h1>
            </div>

            {{-- Tab Filter (Pembelian Saya / Penjualan Saya) --}}
            <div class="mb-6 flex w-full max-w-[340px] rounded-full bg-[#f0f2f1] p-1 text-[14px] font-medium text-[#5c6f68]">
                <a
                    href="{{ route('transactions.index', ['tab' => 'pembelian']) }}"
                    class="flex min-h-11 flex-1 items-center justify-center rounded-full py-2 text-center transition {{ $currentTab === 'pembelian' ? 'bg-white font-semibold text-[#00523f] shadow-sm' : 'hover:text-[#00523f]' }}"
                >
                    Pembelian Saya
                </a>
                <a
                    href="{{ route('transactions.index', ['tab' => 'penjualan']) }}"
                    class="flex min-h-11 flex-1 items-center justify-center rounded-full py-2 text-center transition {{ $currentTab === 'penjualan' ? 'bg-white font-semibold text-[#00523f] shadow-sm' : 'hover:text-[#00523f]' }}"
                >
                    Penjualan Saya
                </a>
            </div>

            @if ($currentTab === 'penjualan')
                @if ($errors->any())
                    <div class="mb-4 rounded-[12px] border border-[#f5c2c7] bg-[#fff3f3] px-4 py-3 text-[13px] text-[#a33c3c]">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="mb-6 rounded-[16px] border border-[#e5e9ed] bg-white p-4 shadow-[0_2px_10px_rgba(22,34,48,0.03)] sm:p-5">
                    <form method="GET" action="{{ route('transactions.index') }}" class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
                        <input type="hidden" name="tab" value="penjualan">

                        <div class="flex flex-wrap items-end gap-3">
                            <div>
                                <label for="filter-start" class="mb-1 block text-[12px] font-medium text-[#526170]">
                                    Dari Tanggal
                                </label>
                                <input
                                    id="filter-start"
                                    type="date"
                                    name="start"
                                    value="{{ $startDate ?? '' }}"
                                    class="min-h-11 w-full rounded-[10px] border border-[#d6dee2] bg-white px-3 text-[14px] text-[#202b3d] focus:border-[#00523f] focus:outline-none sm:w-auto"
                                >
                            </div>
                            <div>
                                <label for="filter-end" class="mb-1 block text-[12px] font-medium text-[#526170]">
                                    Sampai Tanggal
                                </label>
                                <input
                                    id="filter-end"
                                    type="date"
                                    name="end"
                                    value="{{ $endDate ?? '' }}"
                                    class="min-h-11 w-full rounded-[10px] border border-[#d6dee2] bg-white px-3 text-[14px] text-[#202b3d] focus:border-[#00523f] focus:outline-none sm:w-auto"
                                >
                            </div>
                            <button
                                type="submit"
                                class="min-h-11 rounded-[10px] border border-[#00523f] bg-white px-4 text-[14px] font-semibold text-[#00523f] transition hover:bg-[#edf7f4]"
                            >
                                Tampilkan
                            </button>
                            @if (! empty($startDate) || ! empty($endDate))
                                <a
                                    href="{{ route('transactions.index', ['tab' => 'penjualan']) }}"
                                    class="inline-flex min-h-11 items-center rounded-[10px] px-3 text-[14px] font-medium text-[#718096] transition hover:text-[#202b3d]"
                                >
                                    Reset
                                </a>
                            @endif
                        </div>

                        <a
                            href="{{ route('transactions.export', array_filter(['start' => $startDate ?? null, 'end' => $endDate ?? null])) }}"
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-[10px] bg-[#00523f] px-4 py-2.5 text-[14px] font-semibold text-white transition hover:bg-[#003d2e]"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" x2="12" y1="15" y2="3" />
                            </svg>
                            Export Excel
                        </a>
                    </form>
                </div>
            @endif

            @if ($transactions->isEmpty())
                <div class="rounded-[16px] border border-dashed border-[#d6dee2] bg-white px-5 py-16 text-center">
                    <h3 class="font-['Poppins'] text-[18px] font-semibold text-[#263746]">Belum ada transaksi</h3>
                    <p class="mt-2 text-[14px] text-[#718096]">
                        {{ $currentTab === 'penjualan' ? 'Belum ada pembeli yang memesan barangmu.' : 'Kamu belum pernah membuat pesanan COD di kampus.' }}
                    </p>
                    <a href="{{ route('home') }}" class="mt-5 inline-flex rounded-[9px] bg-[#00523f] px-4 py-2.5 text-[13px] font-semibold text-white">
                        Jelajahi Barang Preloved
                    </a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($transactions as $transaction)
                        @php
                            $firstItem = $transaction->items->first();
                            $cover = $firstItem?->product?->media->firstWhere('media_type', 'foto');
                            $contact = $currentTab === 'penjualan' ? $transaction->buyer : $transaction->seller;
                        @endphp
                        <article class="min-w-0 rounded-[16px] border border-[#e5e9ed] bg-white p-4 shadow-[0_2px_10px_rgba(22,34,48,0.03)] transition hover:border-[#00523f]/40 sm:p-6">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#edf0f2] pb-3 text-[12px] text-[#718096]">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-[#202b3d]">Pesanan #{{ $transaction->transaction_id }}</span>
                                    <span>·</span>
                                    <span>{{ $transaction->created_at?->format('d M Y') }}</span>
                                </div>
                                <span class="rounded-full px-3 py-1 font-semibold {{ $transaction->status_badge_class }}">
                                    {{ $transaction->status_label }}
                                </span>
                            </div>

                            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-center gap-3.5">
                                    <div class="size-14 shrink-0 overflow-hidden rounded-[10px] bg-[#f0f1ef]">
                                        @if ($cover)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($cover->media_url) }}" alt="" class="size-full object-cover">
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h2 class="break-words text-[15px] font-semibold text-[#202b3d]">
                                            {{ $firstItem?->item_name_snapshot ?? $firstItem?->product?->product_name ?? 'Barang' }}
                                        </h2>
                                        <p class="mt-0.5 text-[12px] text-[#718096]">
                                            COD: {{ $transaction->meetingPoint?->campus }} ({{ $transaction->meetingPoint?->point_name }})
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-4 border-t border-[#edf0f2] pt-3 sm:border-0 sm:pt-0">
                                    <div class="sm:text-right">
                                        <span class="block text-[11px] text-[#718096]">Total Bayar (COD)</span>
                                        <span class="font-['Poppins'] text-[16px] font-bold text-[#00523f]">
                                            Rp{{ number_format((float) $transaction->total_price, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        @if ($contact)
                                            <a
                                                href="{{ route('transactions.chat', $transaction) }}"
                                                class="inline-flex min-h-11 items-center rounded-[10px] border border-[#00523f] px-4 py-2 text-[14px] font-semibold text-[#00523f] transition hover:bg-[#edf7f4]"
                                            >
                                                Hubungi {{ $currentTab === 'penjualan' ? 'Pembeli' : 'Penjual' }}
                                            </a>
                                        @endif
                                        <a
                                            href="{{ route('transactions.show', $transaction) }}"
                                            class="inline-flex min-h-11 items-center rounded-[10px] bg-[#00523f] px-4 py-2 text-[14px] font-semibold text-white transition hover:bg-[#003d2e]"
                                        >
                                            Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $transactions->links() }}
                </div>
            @endif
        </main>
    </body>
</html>
