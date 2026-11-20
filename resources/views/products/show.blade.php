<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ \Illuminate\Support\Str::limit($product->description, 150) }}">
        <title>{{ $product->product_name }} — Seklov</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f7f9fa] font-['Inter'] text-[#202b3d]">
        <header class="px-4 pt-4 sm:px-6 sm:pt-6">
            <x-navbar />
        </header>

        <main class="mx-auto max-w-[1240px] px-4 pb-14 pt-5 sm:px-6 sm:pt-7">
            <nav class="mb-5 text-[13px] text-[#718096]" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-[#00523f]">Beranda</a>
                <span class="px-2">/</span>
                <a href="{{ route('home', ['kategori' => $product->category_id]) }}" class="hover:text-[#00523f]">{{ $product->category->category_name }}</a>
                <span class="px-2">/</span>
                <span class="font-medium text-[#263746]">{{ $product->product_name }}</span>
            </nav>

            @if (session('success'))
                <p class="mb-5 rounded-[10px] bg-[#e6f7f1] px-4 py-3 text-[14px] text-[#00523f]">{{ session('success') }}</p>
            @endif

            <section class="grid gap-6 rounded-[14px] border border-[#e5e9ed] bg-white p-4 shadow-[0_3px_12px_rgba(22,34,48,0.04)] sm:p-6 lg:grid-cols-2 lg:gap-9" aria-labelledby="product-title">
                <div>
                    @php($cover = $product->media->firstWhere('media_type', 'foto'))
                    <div class="overflow-hidden rounded-[10px] bg-[#f0f1ef]">
                        @if ($cover)
                            <img data-gallery-main src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($cover->media_url) }}" alt="{{ $product->product_name }}" class="aspect-square w-full object-contain">
                        @else
                            <div class="flex aspect-square items-center justify-center text-[14px] text-[#8a969d]">Foto belum tersedia</div>
                        @endif
                    </div>
                    @if ($product->media->where('media_type', 'foto')->count() > 1)
                        <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                            @foreach ($product->media->where('media_type', 'foto') as $photo)
                                <button type="button" data-gallery-thumb="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->media_url) }}" aria-label="Lihat foto {{ $loop->iteration }}" class="size-[72px] shrink-0 overflow-hidden rounded-[8px] border border-[#dce3e6] bg-[#f0f1ef] hover:border-[#00523f]">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->media_url) }}" alt="" class="size-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                    @foreach ($product->media->where('media_type', 'video') as $video)
                        <video controls preload="metadata" class="mt-3 max-h-[320px] w-full rounded-[10px] bg-black">
                            <source src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($video->media_url) }}">
                            Browser tidak mendukung pemutaran video.
                        </video>
                    @endforeach
                </div>

                <div class="flex flex-col py-1 sm:py-2">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="mb-2 text-[12px] font-semibold uppercase tracking-[0.12em] text-[#00a57a]">{{ $product->category->category_name }}</p>
                            <h1 id="product-title" class="font-['Poppins'] text-[24px] leading-tight font-semibold tracking-[-0.03em] sm:text-[29px]">{{ $product->product_name }}</h1>
                        </div>
                        <span class="shrink-0 rounded-full px-3 py-1.5 text-[11px] font-semibold {{ $product->status === 'tersedia' ? 'bg-[#e6f7f1] text-[#00523f]' : 'bg-[#f0f3f6] text-[#526170]' }}">{{ ucfirst($product->status) }}</span>
                    </div>

                    <p class="mt-4 text-[26px] font-bold tracking-[-0.02em] text-[#00523f] sm:text-[30px]">Rp {{ number_format((float) $product->price, 0, ',', '.') }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full bg-[#edf7f4] px-3 py-1.5 text-[12px] font-medium text-[#00523f]">{{ $product->condition }}</span>
                        @if ($product->size)
                            <span class="rounded-full bg-[#f0f3f6] px-3 py-1.5 text-[12px] text-[#526170]">Ukuran {{ $product->size }}</span>
                        @endif
                    </div>

                    <div class="mt-5 border-t border-[#e7ebee] pt-5">
                        <h2 class="text-[14px] font-semibold text-[#263746]">Tentang produk</h2>
                        <p class="mt-2 whitespace-pre-line text-[14px] leading-6 text-[#718096]">{{ $product->description }}</p>
                        @if ($product->usage_period)
                            <p class="mt-3 text-[13px] text-[#526170]"><span class="font-semibold">Lama pemakaian:</span> {{ $product->usage_period }}</p>
                        @endif
                        @if ($product->defects)
                            <p class="mt-2 text-[13px] text-[#526170]"><span class="font-semibold">Kekurangan:</span> {{ $product->defects }}</p>
                        @endif
                    </div>

                    <div class="mt-5 rounded-[10px] bg-[#f7f9fa] p-4">
                        <p class="text-[12px] font-semibold text-[#263746]">Penjual</p>
                        <p class="mt-2 text-[14px] font-semibold">{{ $product->seller->name }}</p>
                        <p class="mt-1 text-[12px] text-[#718096]">{{ $product->seller->campus }}@if ($product->seller->cohort) · Angkatan {{ $product->seller->cohort }}@endif</p>
                    </div>

                    <div class="mt-auto grid grid-cols-1 gap-3 pt-6 sm:grid-cols-2">
                        <button type="button" disabled class="min-h-11 cursor-not-allowed rounded-[9px] bg-[#00523f] px-4 text-[13px] font-semibold text-white opacity-50" title="Fitur chat segera hadir">Chat · Segera hadir</button>
                        @if (auth()->check() && (int) auth()->id() === (int) $product->seller_id)
                            <a href="{{ route('products.edit', $product) }}" class="inline-flex min-h-11 items-center justify-center rounded-[9px] border border-[#00523f] px-4 text-[13px] font-semibold text-[#00523f] hover:bg-[#edf7f4]">Kelola barang</a>
                        @else
                            <button type="button" disabled class="min-h-11 cursor-not-allowed rounded-[9px] border border-[#dce3e6] px-4 text-[13px] font-semibold text-[#526170] opacity-60" title="Fitur negosiasi akan tersedia setelah fase berikutnya">Nego · Segera hadir</button>
                        @endif
                    </div>
                </div>
            </section>

            @if ($relatedProducts->isNotEmpty())
                <section class="mt-10">
                    <div class="mb-4 flex items-end justify-between">
                        <h2 class="font-['Poppins'] text-[21px] font-semibold sm:text-[24px]">Lainnya di {{ $product->category->category_name }}</h2>
                        <a href="{{ route('home', ['kategori' => $product->category_id]) }}" class="text-[12px] font-semibold text-[#00523f]">Lihat semua</a>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                        @foreach ($relatedProducts as $relatedProduct)
                            <x-product-card :product="$relatedProduct" />
                        @endforeach
                    </div>
                </section>
            @endif
        </main>

        <footer class="bg-[#00523f] px-5 py-8 text-white sm:px-8">
            <div class="mx-auto flex max-w-[1240px] flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="font-['Poppins'] text-[19px] font-semibold">Seklov</p>
                <p class="text-[12px] text-white/65">Marketplace preloved mahasiswa Karawang.</p>
            </div>
        </footer>
    </body>
</html>
