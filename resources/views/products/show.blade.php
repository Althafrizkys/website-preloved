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
    <body class="min-h-screen bg-[#f7f9fa] font-['Inter'] text-[#202b3d] antialiased">
        <header class="px-4 pt-4 sm:px-6 sm:pt-5">
            <x-navbar detail />
        </header>

        @php
            $photos = $product->media->where('media_type', 'foto')->values();
            $cover = $photos->first();
            $sellerName = trim((string) ($product->seller?->name ?? '')) ?: 'Penjual Seklov';
            $sellerInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($sellerName, 0, 1));
        @endphp

        <main class="mx-auto max-w-[1076px] px-4 pb-10 pt-5 sm:px-6 sm:pt-7 lg:px-0">
            <nav class="mb-5 truncate text-[12px] text-[#718096] sm:text-[13px]" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-[#00523f]">Beranda</a>
                <span class="px-2">/</span>
                <a href="{{ route('home', ['kategori' => $product->category_id]) }}" class="hover:text-[#00523f]">{{ $product->category->category_name }}</a>
                <span class="px-2">/</span>
                <span class="font-medium text-[#263746]">{{ $product->product_name }}</span>
            </nav>

            @if (session('success'))
                <p class="mb-5 rounded-[10px] bg-[#e6f7f1] px-4 py-3 text-[14px] text-[#00523f]">{{ session('success') }}</p>
            @endif
            @if ($errors->has('product_id') || $errors->has('quantity'))
                <p class="mb-5 rounded-[10px] bg-[#fff5f5] px-4 py-3 text-[14px] text-[#9b3434]" role="alert">{{ $errors->first('product_id') ?: $errors->first('quantity') }}</p>
            @endif

            <section class="grid gap-6 rounded-[14px] border border-[#e5e9ed] bg-white p-4 shadow-[0_3px_12px_rgba(22,34,48,0.04)] sm:p-[22px] lg:grid-cols-[0.83fr_1fr] lg:gap-[42px]" aria-labelledby="product-title">
                <div class="min-w-0">
                    <div class="relative overflow-hidden rounded-[9px] bg-[#f0f1ef]">
                        @if ($cover)
                            <img data-gallery-main src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($cover->media_url) }}" alt="{{ $product->product_name }}" class="aspect-[0.96] w-full object-contain">
                            <span class="absolute bottom-3 right-3 inline-flex items-center gap-1.5 rounded-[7px] bg-white/95 px-2.5 py-1.5 text-[11px] font-medium text-[#526170] shadow-sm">
                                <svg class="size-4 text-[#00523f]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <rect x="4" y="7" width="13" height="13" rx="2" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M8 7V5a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1h-2M7 16l3-3 2 2 2-2 2 2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
                                </svg>
                                <span data-gallery-counter>1/{{ $photos->count() }} foto</span>
                            </span>
                        @else
                            <div class="flex aspect-[0.96] items-center justify-center text-[14px] text-[#8a969d]">Foto belum tersedia</div>
                        @endif
                    </div>

                    @if ($photos->isNotEmpty())
                        <div class="mt-2.5 flex gap-2 overflow-x-auto pb-1">
                            @foreach ($photos as $photo)
                                <button
                                    type="button"
                                    data-gallery-thumb="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->media_url) }}"
                                    aria-label="Lihat foto {{ $loop->iteration }}"
                                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                    class="{{ $loop->first ? 'border-2 border-[#00523f]' : 'border border-[#e5e9ed]' }} aspect-[1.62] w-[32%] shrink-0 overflow-hidden rounded-[8px] bg-[#f0f1ef] hover:border-[#00523f]"
                                >
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->media_url) }}" alt="" class="size-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @foreach ($product->media->where('media_type', 'video') as $video)
                        <video controls preload="metadata" class="mt-3 max-h-[280px] w-full rounded-[9px] bg-black">
                            <source src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($video->media_url) }}">
                            Browser tidak mendukung pemutaran video.
                        </video>
                    @endforeach
                </div>

                <div class="flex min-w-0 flex-col pt-1 sm:pt-2">
                    <div class="flex justify-end">
                        <button type="button" disabled title="Fitur laporan segera hadir" class="inline-flex cursor-not-allowed items-center gap-1.5 text-[11px] text-[#718096] opacity-80">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 21V4m0 1c5-4 9 4 14 0v10c-5 4-9-4-14 0" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
                            </svg>
                            Laporkan
                        </button>
                    </div>

                    <h1 id="product-title" class="mt-3 font-['Poppins'] text-[22px] leading-tight font-semibold tracking-[-0.03em] sm:mt-5 sm:text-[23px]">{{ $product->product_name }}</h1>

                    <div class="mt-3 flex flex-wrap items-center gap-2.5">
                        <p class="font-['Poppins'] text-[25px] leading-none font-bold tracking-[-0.03em] text-[#00523f] sm:text-[27px]">Rp{{ number_format((float) $product->price, 0, ',', '.') }}</p>
                        <span class="rounded-full px-3 py-1.5 text-[11px] font-medium {{ $product->is_negotiable ? 'bg-[#e6f4f0] text-[#236a5a]' : 'bg-[#f0f3f6] text-[#526170]' }}">{{ $product->is_negotiable ? 'Bisa nego' : 'Harga pas' }}</span>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-[#e6f4f0] px-3 py-1.5 text-[11px] font-medium text-[#236a5a]">{{ $product->condition }}</span>
                        <span class="rounded-full bg-[#f0f3f7] px-3 py-1.5 text-[11px] font-medium text-[#526170]">{{ $product->category->category_name }}</span>
                        @if ($product->status !== 'tersedia')
                            <span class="rounded-full bg-[#f0f3f6] px-3 py-1.5 text-[11px] font-medium text-[#526170]">{{ ucfirst($product->status) }}</span>
                        @endif
                        <button type="button" disabled aria-label="Favorit segera hadir" title="Favorit segera hadir" class="flex size-8 cursor-not-allowed items-center justify-center rounded-full border border-[#8daaa2] text-[#718096] opacity-80">
                            <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M20.8 8.8c0 5.2-8.8 10.1-8.8 10.1S3.2 14 3.2 8.8A4.3 4.3 0 0 1 12 6.7a4.3 4.3 0 0 1 8.8 2.1Z" stroke="currentColor" stroke-width="1.7"/>
                            </svg>
                        </button>
                    </div>

                    <div class="mt-4 border-t border-[#e2e7e9] pt-4 sm:mt-4">
                        <h2 class="text-[13px] font-semibold text-[#263746]">Tentang produk</h2>
                        <p class="mt-2 line-clamp-3 whitespace-pre-line text-[14px] leading-[1.7] text-[#718096]">{{ $product->description }}</p>
                    </div>

                    <div class="mt-auto pt-6">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 text-[12px] text-[#526170]">
                            <span>Stok tersedia: {{ number_format($product->stock, 0, ',', '.') }} unit</span>
                            <div class="inline-flex h-9 items-center overflow-hidden rounded-[8px] border border-[#e1e6e9]">
                                <button type="button" data-product-qty-minus aria-label="Kurangi jumlah" @disabled($product->status !== 'tersedia' || $product->stock < 1) class="grid h-full w-9 place-items-center text-[#718096] enabled:hover:bg-[#f7f9fa] disabled:cursor-not-allowed disabled:opacity-50">−</button>
                                <input type="number" data-product-quantity min="1" max="{{ max(1, $product->stock) }}" value="1" aria-label="Jumlah barang yang ingin dibeli" @disabled($product->status !== 'tersedia' || $product->stock < 1) class="h-full w-12 border-x border-[#e1e6e9] p-0 text-center font-semibold text-[#263746] outline-none focus:ring-0">
                                <button type="button" data-product-qty-plus aria-label="Tambah jumlah" @disabled($product->status !== 'tersedia' || $product->stock < 2) class="grid h-full w-9 place-items-center text-[#00523f] enabled:hover:bg-[#f7f9fa] disabled:cursor-not-allowed disabled:opacity-50">+</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_auto_auto]">
                            @if ($product->status !== 'tersedia' || $product->stock < 1)
                                <button type="button" disabled title="Stok barang habis" class="min-h-11 cursor-not-allowed rounded-[8px] bg-[#00523f] px-4 text-[12px] font-semibold text-white opacity-70">Stok Habis</button>
                            @elseif (auth()->check() && (int) auth()->id() === (int) $product->seller_id)
                                <button type="button" disabled title="Penjual tidak dapat membeli barang sendiri" class="min-h-11 cursor-not-allowed rounded-[8px] bg-[#00523f] px-4 text-[12px] font-semibold text-white opacity-70">Beli Sekarang</button>
                            @else
                                <a data-product-buy-link href="{{ route('transactions.create', ['product' => $product, 'quantity' => 1]) }}" class="inline-flex min-h-11 items-center justify-center rounded-[8px] bg-[#00523f] px-4 text-[12px] font-semibold text-white transition hover:bg-[#006c52]">Beli Sekarang</a>
                            @endif
                            @if (! $product->is_negotiable)
                                <button type="button" disabled title="Penjual tidak mengaktifkan negosiasi" class="min-h-11 cursor-not-allowed rounded-[8px] border border-[#dce3e6] px-4 text-[12px] font-semibold text-[#718096] opacity-70">Nego</button>
                            @else
                                @guest
                                    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-[8px] border border-[#dce3e6] px-4 text-[12px] font-semibold text-[#236a5a]">Nego</a>
                                @else
                                    @if ((int) auth()->id() === (int) $product->seller_id)
                                        <button type="button" disabled title="Penjual tidak dapat menawar barang sendiri" class="min-h-11 cursor-not-allowed rounded-[8px] border border-[#dce3e6] px-4 text-[12px] font-semibold text-[#718096] opacity-70">Nego</button>
                                    @elseif (auth()->user()->isAdmin() || $product->status !== 'tersedia' || $product->stock < 1)
                                        <button type="button" disabled title="Negosiasi hanya tersedia untuk barang aktif" class="min-h-11 cursor-not-allowed rounded-[8px] border border-[#dce3e6] px-4 text-[12px] font-semibold text-[#718096] opacity-70">Nego</button>
                                    @else
                                        <button type="button" data-nego-open data-nego-url="{{ route('negotiations.show', $product) }}" class="min-h-11 rounded-[8px] border border-[#dce3e6] px-4 text-[12px] font-semibold text-[#236a5a] transition hover:border-[#00523f] hover:bg-[#f7f9fa]">Nego</button>
                                    @endif
                                @endif
                            @endif
                            @php
                                $cartIcon = '<svg class="size-[17px]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 4h2l2.2 11.1a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 1.9-1.4L21 8H6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/><circle cx="10" cy="20" r="1.2" fill="currentColor"/><circle cx="18" cy="20" r="1.2" fill="currentColor"/></svg>';
                            @endphp
                            @guest
                                <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-[8px] border border-[#dce3e6] px-4 text-[12px] font-semibold text-[#236a5a] transition hover:border-[#00523f] hover:bg-[#f7f9fa]">{!! $cartIcon !!} Keranjang</a>
                            @else
                                @if ((int) auth()->id() === (int) $product->seller_id || $product->status !== 'tersedia' || $product->stock < 1)
                                    <button type="button" disabled title="{{ (int) auth()->id() === (int) $product->seller_id ? 'Tidak bisa memasukkan barang sendiri ke keranjang' : 'Stok barang habis' }}" class="inline-flex min-h-11 cursor-not-allowed items-center justify-center gap-1.5 rounded-[8px] border border-[#dce3e6] px-4 text-[12px] font-semibold text-[#718096] opacity-70">{!! $cartIcon !!} Keranjang</button>
                                @else
                                    <form method="POST" action="{{ route('cart.store') }}" data-product-cart-form class="contents">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->product_id }}">
                                        <input type="hidden" name="quantity" value="1" data-product-cart-quantity>
                                        <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-[8px] border border-[#dce3e6] px-4 text-[12px] font-semibold text-[#236a5a] transition hover:border-[#00523f] hover:bg-[#f7f9fa]">{!! $cartIcon !!} Keranjang</button>
                                    </form>
                                @endif
                            @endguest
                        </div>
                    </div>
                </div>
            </section>

            <section class="mt-5 flex flex-col gap-4 rounded-[12px] border border-[#e5e9ed] bg-white p-4 shadow-[0_2px_8px_rgba(22,34,48,0.025)] sm:flex-row sm:items-center sm:justify-between sm:px-4 sm:py-3" aria-label="Informasi penjual">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($product->seller)
                        <x-avatar :user="$product->seller" size="size-14" text="text-[17px]" />
                    @else
                        <div class="grid size-14 shrink-0 place-items-center overflow-hidden rounded-full bg-[#e9d8e6] font-['Poppins'] text-[17px] font-semibold text-[#00523f]" aria-hidden="true">{{ $sellerInitial }}</div>
                    @endif
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-[13px] font-semibold text-[#263746]">{{ $sellerName }}</p>
                            @if ($product->seller?->campus)
                                <span class="rounded-full bg-[#e6f4f0] px-3 py-1 text-[10px] font-medium text-[#236a5a]">{{ $product->seller->campus }}</span>
                            @endif
                        </div>
                        @if ($product->seller?->cohort)
                            <p class="mt-1 text-[11px] text-[#718096]">Angkatan {{ $product->seller->cohort }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    @php
                        $chatBlocked = auth()->check() && (auth()->user()->isAdmin() || (int) auth()->id() === (int) $product->seller_id);
                    @endphp
                    <form method="{{ auth()->check() ? 'POST' : 'GET' }}" action="{{ auth()->check() ? route('chat.start', $product) : route('login') }}">
                        @auth
                            @csrf
                        @endauth
                        <button type="submit" @disabled($chatBlocked) @if ($chatBlocked) title="Anda tidak dapat memulai chat di sini" @endif class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-[8px] bg-[#00523f] px-4 text-[12px] font-semibold text-white {{ $chatBlocked ? 'cursor-not-allowed opacity-80' : 'hover:bg-[#006c52]' }}">
                            <svg class="size-[17px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
                            </svg>
                            Chat Sekarang
                        </button>
                    </form>
                                        @if (auth()->check() && (int) auth()->id() === (int) $product->seller_id)
                        <a href="{{ route('products.edit', $product) }}" class="inline-flex min-h-10 items-center justify-center rounded-[8px] border border-[#dce3e6] px-4 text-[12px] font-semibold text-[#236a5a] hover:bg-[#f7f9fa]">Kelola barang</a>
                    @elseif ($product->seller)
                        <a href="{{ route('store.show', $product->seller) }}" title="Lihat profil penjual" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-[8px] border border-[#dce3e6] px-4 text-[12px] font-semibold text-[#236a5a] transition hover:bg-[#edf7f4]">
                            <svg class="size-[17px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <circle cx="12" cy="7" r="4"/>
                                <path d="M4 20a8 8 0 0 1 16 0H4Z"/>
                            </svg>
                            Kunjungi Profile
                        </a>
                    @endif
                </div>
            </section>

            <section class="mt-5 rounded-[12px] border border-[#e5e9ed] bg-white p-4 sm:p-5" aria-labelledby="description-title">
                <h2 id="description-title" class="font-['Poppins'] text-[15px] font-semibold text-[#263746]">Deskripsi Produk</h2>
                <p class="mt-2 whitespace-pre-line text-[14px] leading-[1.8] text-[#718096]">{{ $product->description }}</p>
                @if ($product->usage_period || $product->defects)
                    <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-[12px] text-[#526170]">
                        @if ($product->usage_period)
                            <p><span class="font-semibold">Lama pemakaian:</span> {{ $product->usage_period }}</p>
                        @endif
                        @if ($product->defects)
                            <p><span class="font-semibold">Kekurangan:</span> {{ $product->defects }}</p>
                        @endif
                    </div>
                @endif
            </section>

            <section class="relative left-1/2 mt-5 w-[calc(100vw-2rem)] max-w-[1160px] -translate-x-1/2 sm:mt-7" aria-labelledby="related-title">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <h2 id="related-title" class="font-['Poppins'] text-[18px] font-semibold text-[#202b3d] sm:text-[20px]">Lainnya</h2>
                    <a href="{{ route('home', ['kategori' => $product->category_id]) }}" class="text-[11px] font-medium text-[#00523f] hover:underline">Lihat semua</a>
                </div>
                @if ($relatedProducts->isNotEmpty())
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4 sm:gap-5">
                        @foreach ($relatedProducts as $relatedProduct)
                            <a href="{{ route('products.show', $relatedProduct) }}" class="group flex min-w-0 flex-col overflow-hidden rounded-[17px] bg-white shadow-[0_2px_10px_rgba(22,34,48,0.045)] transition hover:-translate-y-0.5 hover:shadow-[0_8px_20px_rgba(22,34,48,0.09)]">
                                <div class="aspect-[0.82] overflow-hidden bg-[#f0f1ef]">
                                    @if ($relatedProduct->media->isNotEmpty())
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($relatedProduct->media->first()->media_url) }}" alt="{{ $relatedProduct->product_name }}" class="size-full object-cover transition duration-300 group-hover:scale-[1.03]">
                                    @else
                                        <div class="flex size-full items-center justify-center text-[12px] text-[#8a969d]">Foto belum tersedia</div>
                                    @endif
                                </div>
                                <div class="flex min-h-[88px] flex-1 flex-col p-3">
                                    <p class="line-clamp-1 text-[13px] font-medium text-[#236a5a] sm:text-[15px]">{{ $relatedProduct->product_name }}</p>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-1">
                                        <span class="rounded-full bg-[#00523f] px-3 py-1 text-[10px] font-medium text-white">{{ $relatedProduct->condition }}</span>
                                        <span data-product-category="{{ $relatedProduct->category?->category_name ?? 'Lainnya' }}" class="rounded-full bg-[#f0f3f6] px-2.5 py-1 text-[10px] font-medium text-[#526170]">{{ $relatedProduct->category?->category_name ?? 'Lainnya' }}</span>
                                    </div>
                                    <p class="mt-auto pt-2 text-right text-[11px] font-medium text-[#236a5a]">Rp{{ number_format((float) $relatedProduct->price, 0, ',', '.') }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-[12px] border border-dashed border-[#d6dee2] bg-white px-4 py-6 text-center text-[12px] text-[#718096]">Belum ada barang lain yang tersedia.</p>
                @endif
            </section>

            <dialog
                data-nego-dialog
                data-nego-url="{{ route('negotiations.show', $product) }}"
                data-nego-auto-open="{{ request()->query('nego') === 'open' ? 'true' : 'false' }}"
                aria-label="Nego harga produk"
                class="m-auto max-h-[88vh] w-[90vw] max-w-[960px] overflow-y-auto rounded-[24px] bg-transparent p-0 text-[#202b3d] shadow-[0_20px_70px_rgba(25,40,52,0.22)] backdrop:bg-[#202b3d]/35"
            >
                <div data-nego-dialog-content></div>
            </dialog>
        </main>

        <footer class="mt-1 bg-[#00523f] px-5 py-7 text-white sm:px-8 sm:py-9">
            <div class="mx-auto flex max-w-[1076px] items-center justify-between">
                <p class="font-['Poppins'] text-[19px] font-semibold">Seklov</p>
                <span class="text-[11px] text-white/75">about us</span>
            </div>
        </footer>
    </body>
</html>