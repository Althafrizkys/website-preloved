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
    </head>
    <body class="m-0 bg-[#f7f9fa] font-['Inter'] text-[#202b3d] antialiased">
        <header class="px-4 pt-4 sm:px-6 sm:pt-6">
            <x-navbar />
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

            <section class="mt-5 rounded-[14px] border border-[#e7ebee] bg-white p-3 shadow-[0_3px_12px_rgba(22,34,48,0.04)] sm:mt-7 sm:p-4" aria-label="Cari dan filter barang">
                <form method="GET" action="{{ route('home') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_220px_auto]">
                    <label class="flex min-h-11 items-center gap-2 rounded-[10px] border border-[#e1e6e9] px-3.5 focus-within:border-[#00523f]">
                        <svg class="size-[18px] shrink-0 text-[#718096]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.8"/>
                            <path d="m16 16 4.2 4.2" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/>
                        </svg>
                        <span class="sr-only">Cari nama barang</span>
                        <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama barang..." class="min-w-0 flex-1 border-0 bg-transparent text-[14px] text-[#202b3d] outline-none placeholder:text-[#98a2ad] focus:ring-0">
                    </label>

                    <label class="flex min-h-11 items-center rounded-[10px] border border-[#e1e6e9] px-3.5">
                        <span class="sr-only">Filter kategori</span>
                        <select name="kategori" class="w-full border-0 bg-transparent text-[14px] text-[#526170] outline-none focus:ring-0">
                            <option value="">Semua kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->category_id }}" @selected($selectedCategory === (string) $category->category_id)>{{ $category->category_name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <button type="submit" class="min-h-11 rounded-[10px] bg-[#00523f] px-5 text-[14px] font-semibold text-white transition hover:bg-[#006c52]">Cari barang</button>
                </form>
            </section>

            <div class="mt-4 mb-6">
                <img
                    src="{{ asset('images/banner-wishlist.jpeg') }}"
                    alt="Banner wishlist: minta barang yang belum tersedia"
                    loading="lazy"
                    class="block h-auto w-full rounded-3xl"
                >
            </div>

            <section class="mt-8 sm:mt-10">
                <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-[12px] font-semibold uppercase tracking-[0.13em] text-[#00a57a]">Pilihan kampus</p>
                        <h2 class="mt-1 font-['Poppins'] text-[20px] font-semibold tracking-[-0.025em] text-[#202b3d] sm:text-[24px]">Kategori barang</h2>
                    </div>
                </div>
                <div class="flex gap-2.5 overflow-x-auto pb-2">
                    <a href="{{ route('home', array_filter(['q' => $search])) }}" class="shrink-0 rounded-full border px-4 py-2 text-[13px] font-medium transition {{ $selectedCategory === '' ? 'border-[#00523f] bg-[#00523f] text-white' : 'border-[#e1e6e9] bg-white text-[#526170] hover:border-[#00523f] hover:text-[#00523f]' }}">Semua</a>
                    @foreach ($categories as $category)
                        <a href="{{ route('home', array_filter(['q' => $search, 'kategori' => $category->category_id])) }}" class="shrink-0 rounded-full border px-4 py-2 text-[13px] font-medium transition {{ $selectedCategory === (string) $category->category_id ? 'border-[#00523f] bg-[#00523f] text-white' : 'border-[#e1e6e9] bg-white text-[#526170] hover:border-[#00523f] hover:text-[#00523f]' }}">{{ $category->category_name }}</a>
                    @endforeach
                </div>
            </section>

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
                        <p class="mt-2 text-[14px] text-[#718096]">Coba kata pencarian atau kategori yang berbeda.</p>
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
    </body>
</html>
