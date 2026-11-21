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
            <div class="mb-6 flex max-w-[340px] rounded-full bg-[#f0f2f1] p-1 text-[13px] font-medium text-[#5c6f68]">
                <a
                    href="{{ route('transactions.index', ['tab' => 'pembelian']) }}"
                    class="flex-1 rounded-full py-2 text-center transition {{ $currentTab === 'pembelian' ? 'bg-white font-semibold text-[#00523f] shadow-sm' : 'hover:text-[#00523f]' }}"
                >
                    Pembelian Saya
                </a>
                <a
                    href="{{ route('transactions.index', ['tab' => 'penjualan']) }}"
                    class="flex-1 rounded-full py-2 text-center transition {{ $currentTab === 'penjualan' ? 'bg-white font-semibold text-[#00523f] shadow-sm' : 'hover:text-[#00523f]' }}"
                >
                    Penjualan Saya
                </a>
            </div>

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
                        <article class="rounded-[16px] border border-[#e5e9ed] bg-white p-5 shadow-[0_2px_10px_rgba(22,34,48,0.03)] transition hover:border-[#00523f]/40 sm:p-6">
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
                                <div class="flex items-center gap-3.5">
                                    <div class="size-14 shrink-0 overflow-hidden rounded-[10px] bg-[#f0f1ef]">
                                        @if ($cover)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($cover->media_url) }}" alt="" class="size-full object-cover">
                                        @endif
                                    </div>
                                    <div>
                                        <h2 class="text-[15px] font-semibold text-[#202b3d]">
                                            {{ $firstItem?->product?->product_name ?? 'Barang' }}
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
                                                href="mailto:{{ $contact->email }}"
                                                class="rounded-[10px] border border-[#00523f] px-4 py-2 text-[12px] font-semibold text-[#00523f] transition hover:bg-[#edf7f4]"
                                            >
                                                Hubungi {{ $currentTab === 'penjualan' ? 'Pembeli' : 'Penjual' }}
                                            </a>
                                        @endif
                                        <a
                                            href="{{ route('transactions.show', $transaction) }}"
                                            class="rounded-[10px] bg-[#00523f] px-4 py-2 text-[12px] font-semibold text-white transition hover:bg-[#003d2e]"
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
