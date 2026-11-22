<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Pesan — Seklov</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white font-['Inter'] text-[#202b3d] antialiased">
        <header class="px-4 pt-4 sm:px-6 sm:pt-6">
            <x-navbar />
        </header>

        <main class="mx-auto max-w-[1320px] px-4 pb-10 pt-6 sm:px-6">
            <a href="{{ $active?->product ? route('products.show', $active->product) : route('home') }}" class="mb-3 inline-flex items-center gap-2 text-[13px] text-[#263746] hover:text-[#00523f]">
                <span aria-hidden="true">←</span> {{ $active ? 'Kembali ke produk' : 'Kembali ke beranda' }}
            </a>

            <div class="flex h-[calc(100vh-220px)] min-h-[560px] overflow-hidden rounded-[8px] border border-[#e1e6e9] bg-white">
                <aside class="{{ $active ? 'hidden lg:flex' : 'flex' }} w-full shrink-0 flex-col border-r border-[#e7ebee] p-4 lg:w-[320px]">
                    <div class="flex items-center justify-between">
                        <h1 class="font-['Poppins'] text-[22px] font-semibold text-[#202b3d]">Pesan</h1>
                        <span class="rounded-full bg-[#f0f2f1] px-2.5 py-1 text-[11px] text-[#718096]">{{ $conversations->count() }} chat</span>
                    </div>

                    @php
                        $tabs = ['semua' => 'Semua', 'penjual' => 'Sebagai Penjual', 'pembeli' => 'Sebagai Pembeli', 'belum' => 'Belum Dibaca'];
                    @endphp
                    <nav class="mt-4 grid grid-cols-4 gap-1 rounded-[12px] bg-[#f0f2f1] p-1 text-center text-[11px] font-medium text-[#5c6f68]" aria-label="Filter percakapan">
                        @foreach ($tabs as $key => $label)
                            <a href="{{ route('chat.index', array_filter(['filter' => $key !== 'semua' ? $key : null, 'q' => $search !== '' ? $search : null])) }}"
                               class="rounded-[10px] px-1 py-2 leading-tight {{ $filter === $key ? 'bg-[#00523f] font-semibold text-white' : 'hover:text-[#00523f]' }}">{{ $label }}</a>
                        @endforeach
                    </nav>

                    <form method="GET" action="{{ route('chat.index') }}" class="mt-3">
                        @if ($filter !== 'semua')
                            <input type="hidden" name="filter" value="{{ $filter }}">
                        @endif
                        <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama atau pesan..." class="min-h-10 w-full rounded-[10px] border-0 bg-[#f0f2f1] px-4 text-[13px] placeholder:text-[#9aa5ae] focus:outline-none focus:ring-2 focus:ring-[#8fd0bb]">
                    </form>

                    <div class="mt-3 min-h-0 flex-1 space-y-1 overflow-y-auto">
                        @forelse ($conversations as $conversation)
                            @include('chat._item', ['conversation' => $conversation])
                        @empty
                            <p class="rounded-[10px] bg-[#f7f9fa] px-4 py-6 text-center text-[12px] text-[#718096]">
                                {{ $search !== '' || $filter !== 'semua' ? 'Tidak ada percakapan yang cocok.' : 'Belum ada percakapan.' }}
                            </p>
                        @endforelse
                    </div>

                    <div class="mt-3 rounded-[12px] bg-[#f5f7f7] p-4">
                        <p class="text-[12px] font-semibold text-[#00523f]">Tetap aman di Seklov</p>
                        <p class="mt-1 text-[11px] leading-relaxed text-[#718096]">Jangan bagikan kode OTP atau informasi pribadi melalui chat.</p>
                    </div>
                </aside>

                @if ($active)
                    @include('chat._thread')
                @else
                    <section class="hidden flex-1 items-center justify-center bg-[#f7f9fa] text-[13px] text-[#718096] lg:flex">
                        Pilih percakapan untuk mulai membaca pesan.
                    </section>
                @endif
            </div>
        </main>
    </body>
</html>