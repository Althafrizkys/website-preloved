<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Jelajahi barang preloved mahasiswa UNSIKA dan UBP di Seklov.">
        <title>Seklov — Marketplace Preloved Mahasiswa</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>@view-transition { navigation: auto; }</style>
    </head>
    <body class="m-0 bg-[#f7f9fa] font-['Inter'] text-[#202b3d] antialiased" style="zoom: 80%">
        <header class="px-4 pt-4 sm:px-6 sm:pt-6">
            <x-navbar :search="$search" :filters="$activeFilters" />
        </header>

        <main class="mx-auto max-w-[1320px] px-4 pb-16 pt-6 sm:px-6 sm:pt-8">
            <section class="relative grid min-h-[360px] grid-cols-1 items-end overflow-hidden rounded-3xl bg-gradient-to-r from-[#00523F] to-[#10B98A] px-6 pt-8 text-white shadow-[0_8px_24px_rgba(0,82,63,0.16)] sm:min-h-[400px] sm:px-10 sm:pt-10 lg:min-h-[320px] lg:grid-cols-[1.1fr_0.9fr] lg:px-12 lg:pt-10">
                <div class="relative z-10 pb-2 sm:pb-4 lg:max-w-[620px] lg:pb-10">
                    <h1 class="font-['Poppins'] text-[28px] leading-[1.2] font-semibold tracking-[-0.035em] sm:text-[38px] lg:text-[44px]">Dari Mahasiswa Untuk Mahasiswa!</h1>
                    <p class="mt-3 text-[15px] leading-6 text-white/90 sm:text-[17px]">Transaksi aman bisa COD di Kampus.</p>
                    <x-sell-button label="Mulai Jual" class="mt-6 inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-white px-5 py-2.5 text-[14px] font-bold text-[#00523F] shadow-sm transition hover:bg-[#e8fff7]">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </x-sell-button>
                </div>
                <img
                    src="{{ asset('images/karakter-mahasiswa.png') }}"
                    alt="Dua mahasiswa berbagi buku"
                    class="relative ml-auto mt-3 h-40 max-w-[78%] object-contain object-bottom sm:h-52 lg:absolute lg:bottom-0 lg:right-8 lg:mt-0 lg:h-full lg:max-w-[44%]"
                >
            </section>

            @php
                $pillBase = 'flex h-9 cursor-pointer select-none list-none items-center gap-1.5 rounded-[10px] border px-3.5 text-[13px] transition [&::-webkit-details-marker]:hidden';
                $pillIdle = 'border-[#e1e6e9] bg-white text-[#526170] hover:border-[#00523f]/40';
                $pillActive = 'border-[#00523f] bg-[#edf7f4] font-medium text-[#00523f]';
                $panelBase = 'absolute left-0 top-full z-30 mt-2 w-64 rounded-[12px] border border-[#e1e6e9] bg-white p-1.5 shadow-[0_10px_30px_rgba(22,34,48,0.12)]';
                $rowBase = 'group flex cursor-pointer items-center justify-between rounded-[8px] px-3 py-2.5 text-[13px] text-[#263746] transition hover:bg-[#f7f9fa] has-[:checked]:bg-[#edf7f4] has-[:checked]:font-semibold has-[:checked]:text-[#00523f]';

                $filterMenus = [
                    [
                        'name' => 'kategori',
                        'label' => 'Kategori',
                        'all' => 'Semua kategori',
                        'selected' => $selectedCategory,
                        'options' => $categories->mapWithKeys(fn ($category) => [(string) $category->category_id => $category->category_name])->all(),
                    ],
                    [
                        'name' => 'kondisi',
                        'label' => 'Kondisi',
                        'all' => 'Semua kondisi',
                        'selected' => $selectedCondition,
                        'options' => collect($conditions)->mapWithKeys(fn ($condition) => [$condition => $condition])->all(),
                    ],
                    [
                        'name' => 'nego',
                        'label' => 'Nego',
                        'all' => 'Semua',
                        'selected' => $selectedNego,
                        'options' => ['ya' => 'Bisa nego', 'tidak' => 'Tidak bisa nego'],
                    ],
                ];

                $hasPrice = $minPrice !== '' || $maxPrice !== '';
                $priceLabel = $hasPrice
                    ? 'Harga: '.($minPrice !== '' ? number_format((int) $minPrice, 0, ',', '.') : '0').' – '.($maxPrice !== '' ? number_format((int) $maxPrice, 0, ',', '.') : '∞')
                    : 'Harga';
            @endphp

            <section id="filter-barang" class="mt-5 scroll-mt-4 rounded-[14px] border border-[#e7ebee] bg-white px-3 py-3 shadow-[0_3px_12px_rgba(22,34,48,0.04)] sm:mt-7 sm:px-4" aria-label="Filter barang">
                <form method="GET" action="{{ route('home') }}#filter-barang" onchange="if (event.target.type === 'radio') this.submit()" class="flex flex-wrap items-center gap-2">
                    @if ($search !== '')
                        <input type="hidden" name="q" value="{{ $search }}">
                    @endif

                    @foreach ($filterMenus as $menu)
                        @php
                            $selectedLabel = $menu['selected'] !== '' ? ($menu['options'][$menu['selected']] ?? null) : null;
                        @endphp
                        <details name="filter-menu" class="relative">
                            <summary class="{{ $pillBase }} {{ $selectedLabel ? $pillActive : $pillIdle }}">
                                <span>{{ $selectedLabel ? $menu['label'].': '.$selectedLabel : $menu['label'] }}</span>
                                <svg class="size-3.5 opacity-70" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                            </summary>
                            <div class="{{ $panelBase }}">
                                <div class="max-h-60 overflow-y-auto">
                                    <label class="{{ $rowBase }}">
                                        <input type="radio" name="{{ $menu['name'] }}" value="" class="sr-only" @checked($menu['selected'] === '')>
                                        <span>{{ $menu['all'] }}</span>
                                    </label>
                                    @foreach ($menu['options'] as $value => $optionLabel)
                                        <label class="{{ $rowBase }}">
                                            <input type="radio" name="{{ $menu['name'] }}" value="{{ $value }}" class="sr-only" @checked($menu['selected'] === (string) $value)>
                                            <span>{{ $optionLabel }}</span>
                                            <svg class="hidden size-4 group-has-[:checked]:block" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 12 5 5 9-10" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                    @endforeach

                    <details name="filter-menu" class="relative" @if ($hasPrice) open @endif>
                        <summary class="{{ $pillBase }} {{ $hasPrice ? $pillActive : $pillIdle }}">
                            <span>{{ $priceLabel }}</span>
                            <svg class="size-3.5 opacity-70" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                        </summary>
                        <div class="{{ $panelBase }} w-72 p-4">
                            <p class="text-[13px] font-semibold text-[#263746]">Rentang harga</p>
                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <div>
                                    <label for="harga_min" class="text-[11px] font-medium text-[#718096]">Harga minimum</label>
                                    <div class="mt-1 flex h-10 items-center overflow-hidden rounded-[8px] border border-[#e1e6e9] bg-[#f7f9fa] transition focus-within:border-[#00523f] focus-within:bg-white">
                                        <span class="flex h-full items-center border-r border-[#e1e6e9] px-2.5 text-[12px] font-medium text-[#718096]">Rp</span>
                                        <input id="harga_min" type="number" name="harga_min" min="0" inputmode="numeric" value="{{ $minPrice }}" placeholder="0" class="h-full w-full min-w-0 border-0 bg-transparent px-2.5 text-[13px] text-[#202b3d] placeholder:text-[#a0aab4] focus:ring-0">
                                    </div>
                                </div>
                                <div>
                                    <label for="harga_max" class="text-[11px] font-medium text-[#718096]">Harga maksimum</label>
                                    <div class="mt-1 flex h-10 items-center overflow-hidden rounded-[8px] border border-[#e1e6e9] bg-[#f7f9fa] transition focus-within:border-[#00523f] focus-within:bg-white">
                                        <span class="flex h-full items-center border-r border-[#e1e6e9] px-2.5 text-[12px] font-medium text-[#718096]">Rp</span>
                                        <input id="harga_max" type="number" name="harga_max" min="0" inputmode="numeric" value="{{ $maxPrice }}" placeholder="Bebas" class="h-full w-full min-w-0 border-0 bg-transparent px-2.5 text-[13px] text-[#202b3d] placeholder:text-[#a0aab4] focus:ring-0">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center gap-3">
                                <button type="submit" class="h-10 flex-1 rounded-[8px] bg-[#00523f] text-[13px] font-semibold text-white transition hover:bg-[#006c52]">Terapkan</button>
                                @if ($hasPrice)
                                    <a href="{{ route('home', array_filter(array_merge(['q' => $search], \Illuminate\Support\Arr::except($activeFilters, ['harga_min', 'harga_max'])))) }}" class="text-[12px] font-medium text-[#718096] underline hover:text-[#00523f]">Hapus</a>
                                @endif
                            </div>
                        </div>
                    </details>

                    @if ($activeFilters !== [])
                        <a href="{{ route('home', array_filter(['q' => $search])) }}" class="px-1 text-[12px] font-medium text-[#00523f] underline">Reset filter</a>
                    @endif

                    <x-sell-button label="+ Mulai Jual" class="ml-auto inline-flex h-10 items-center justify-center rounded-[10px] bg-[#00523f] px-5 text-[13px] font-semibold text-white transition hover:bg-[#006c52]" />
                </form>
            </section>

            <div class="mt-4 mb-6">
                 <a href="{{ route('wishlist.index') }}" aria-label="Lihat permintaan barang (wishlist)" class="block rounded-3xl transition hover:opacity-95">
                <img
                    src="{{ asset('images/banner-wishlist.jpeg') }}"
                    alt="Banner wishlist: minta barang yang belum tersedia"
                    loading="lazy"
                    class="block h-auto w-full rounded-3xl"
                >
                </a>
            </div>

            <section class="mt-7 sm:mt-9">
                <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-[12px] font-semibold uppercase tracking-[0.13em] text-[#00a57a]">Temukan barang pilihanmu</p>
                        <h2 class="mt-1 font-['Poppins'] text-[21px] font-semibold tracking-[-0.025em] text-[#202b3d] sm:text-[26px]">Barang terbaru</h2>
                    </div>
                    <p class="text-[13px] text-[#718096]">{{ $products->total() }} barang</p>
                </div>

                @if ($products->isNotEmpty())
                    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
                        @foreach ($products as $product)
                            <x-product-card :product="$product" />
                        @endforeach
                    </div>
                    <div class="mt-8">
                        {{ $products->links() }}
                    </div>
                @else
                    <div class="rounded-[16px] border border-dashed border-[#d6dee2] bg-white px-5 py-12 text-center">
                        <h3 class="font-['Poppins'] text-[18px] font-semibold text-[#263746]">Belum ada barang yang cocok</h3>
                        <p class="mt-2 text-[14px] text-[#718096]">Coba ubah kata pencarian atau filter.</p>
                        <a href="{{ route('home') }}" class="mt-5 inline-flex rounded-[9px] bg-[#00523f] px-4 py-2.5 text-[13px] font-semibold text-white">Lihat semua barang</a>
                    </div>
                @endif
            </section>
        </main>

        <footer class="bg-[#00523f] px-5 py-8 text-white sm:px-8 sm:py-10">
            <div class="mx-auto flex max-w-[1320px] flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-['Poppins'] text-[20px] font-semibold">Seklov</p>
                    <p class="mt-1 text-[13px] text-white/70">Marketplace preloved mahasiswa Karawang.</p>
                </div>
                <p class="text-[12px] text-white/65">© {{ date('Y') }} Seklov. Untuk mahasiswa UNSIKA &amp; UBP.</p>
            </div>
        </footer>
        <a href="{{ auth()->check() ? route('chat.index') : route('login') }}" title="Pesan" aria-label="Buka pesan" class="fixed bottom-6 right-6 z-40 flex size-14 items-center justify-center rounded-full bg-[#1a8a68] text-white shadow-lg transition hover:bg-[#1f9d78]">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
            </svg>
        </a>
    </body>
</html>
