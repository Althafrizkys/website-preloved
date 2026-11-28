<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Keranjang Belanja — Seklov</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f8fafc] font-['Inter'] text-[#1e293b] antialiased">
        <header class="px-4 pt-4 sm:px-6 sm:pt-6">
            <x-navbar />
        </header>

        <main class="mx-auto max-w-[1200px] px-4 pt-6 pb-36 sm:px-6" data-cart-page>
            <nav class="mb-5 text-[13px] text-[#718096]" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-[#00523f]">Beranda</a>
                <span class="px-2">›</span>
                <span class="font-medium text-[#00523f]">Keranjang</span>
            </nav>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="font-['Poppins'] text-[24px] font-bold text-[#1e293b] sm:text-[28px]">Keranjang Belanja</h1>

                @if ($itemCount > 0)
                    <div class="flex flex-wrap items-center gap-3">
                        <label class="flex cursor-pointer items-center gap-3">
                            <input type="checkbox" data-cart-select-all class="size-5 cursor-pointer rounded-[6px] border-2 border-[#cbd5e1] text-[#00523f] focus:ring-[#00523f]">
                            <span class="text-[14px] font-semibold text-[#1e293b]">Pilih Semua Barang (<span data-cart-group-count>0</span>)</span>
                        </label>
                        <form method="POST" action="{{ route('cart.destroy-selected') }}" data-cart-delete-selected>
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-[8px] border border-[#e2e8f0] bg-white px-3 py-2 text-[13px] font-semibold text-[#00523f] transition hover:border-[#00523f] disabled:cursor-not-allowed disabled:opacity-50" data-cart-delete-selected-button disabled>
                                Hapus Diterapkan
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            @if (session('success'))
                <p class="mt-5 rounded-[10px] bg-[#e6f7f1] px-4 py-3 text-[14px] text-[#00523f]" role="status">{{ session('success') }}</p>
            @endif
            @if (session('error'))
                <p class="mt-5 rounded-[10px] bg-[#fff5f5] px-4 py-3 text-[14px] text-[#9b3434]" role="alert">{{ session('error') }}</p>
            @endif
            <p class="mt-5 hidden rounded-[10px] bg-[#fff8e6] px-4 py-3 text-[13px] text-[#8a5a00]" role="status" data-cart-seller-notice>
                Checkout dilakukan per penjual. Pilihan dari penjual lain sudah dilepas.
            </p>

            @if ($itemCount === 0)
                <section class="mt-6 rounded-[12px] border border-dashed border-[#cbd5e1] bg-white px-6 py-14 text-center">
                    <p class="font-['Poppins'] text-[18px] font-bold text-[#1e293b]">Keranjangmu masih kosong</p>
                    <p class="mt-2 text-[14px] text-[#64748b]">Temukan barang preloved dari mahasiswa kampus dan tambahkan ke keranjang.</p>
                    <a href="{{ route('home') }}" class="mt-5 inline-flex items-center rounded-[12px] bg-[#00523f] px-6 py-3 font-['Poppins'] text-[15px] font-bold text-white transition hover:bg-[#006c52]">Cari barang</a>
                </section>
            @else
                <div class="mt-6 flex flex-col gap-4">
                    <div class="hidden items-center gap-3 rounded-[12px] border border-[#e2e8f0] bg-white p-4 text-[13px] font-semibold text-[#64748b] md:flex">
                        <p class="min-w-0 flex-1">Produk</p>
                        <p class="w-[140px] shrink-0">Harga Satuan</p>
                        <p class="w-[128px] shrink-0">Kuantitas</p>
                        <p class="w-[140px] shrink-0">Total Harga</p>
                        <p class="w-[80px] shrink-0 text-center">Aksi</p>
                    </div>

                    @foreach ($groups as $sellerId => $groupItems)
                        @php $seller = $groupItems->first()->product->seller; @endphp
                        <section class="flex flex-col gap-3" aria-label="Barang dari {{ $seller?->name ?? 'Penjual' }}">
                            <p class="px-1 text-[13px] font-semibold text-[#64748b]">
                                Penjual: <span class="text-[#1e293b]">{{ $seller?->name ?? 'Penjual' }}</span>
                                @if ($seller?->campus)
                                    <span class="ml-1 rounded-full bg-[#f1f5f9] px-2 py-0.5 text-[12px]">{{ $seller->campus }}</span>
                                @endif
                            </p>

                            @foreach ($groupItems as $item)
                                @php
                                    $product = $item->product;
                                    $cover = $product->media->first();
                                    $lineTotal = $item->unit_price * $item->quantity;
                                @endphp
                                <article
                                    class="flex flex-col gap-4 rounded-[12px] border border-[#e2e8f0] bg-white p-4 sm:p-5 md:flex-row md:items-center md:gap-3"
                                    data-cart-item
                                    data-item-id="{{ $item->cart_item_id }}"
                                    data-seller-id="{{ $sellerId }}"
                                    data-unit-price="{{ $item->unit_price }}"
                                    data-quantity="{{ $item->quantity }}"
                                >
                                    <div class="flex min-w-0 flex-1 items-center gap-3">
                                        <input
                                            type="checkbox"
                                            value="{{ $item->cart_item_id }}"
                                            aria-label="Pilih {{ $product->product_name }}"
                                            data-cart-checkbox
                                            @disabled(! $item->is_available)
                                            class="size-5 shrink-0 cursor-pointer rounded-[6px] border-2 border-[#cbd5e1] text-[#00523f] focus:ring-[#00523f] disabled:cursor-not-allowed disabled:opacity-40"
                                        >
                                        <a href="{{ route('products.show', $product) }}" class="size-20 shrink-0 overflow-hidden rounded-[10px] bg-[#f1f5f9]">
                                            @if ($cover)
                                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($cover->media_url) }}" alt="{{ $product->product_name }}" class="size-full object-cover">
                                            @else
                                                <span class="flex size-full items-center justify-center text-[11px] text-[#94a3b8]">Foto</span>
                                            @endif
                                        </a>
                                        <div class="flex min-w-0 flex-1 flex-col items-start gap-1.5">
                                            <a href="{{ route('products.show', $product) }}" class="line-clamp-2 font-['Poppins'] text-[16px] font-bold text-[#1e293b] hover:text-[#00523f]">{{ $product->product_name }}</a>
                                            <p class="text-[13px] text-[#64748b]">Penjual kampus</p>
                                            @if ($product->seller?->campus)
                                                <span class="rounded-full bg-[#f1f5f9] px-2 py-1 text-[12px] font-semibold text-[#64748b]">{{ $product->seller->campus }}</span>
                                            @endif
                                            @unless ($item->is_available)
                                                <span class="text-[12px] font-semibold text-[#b91c1c]">
                                                    {{ $product->status !== 'tersedia' || $product->stock < 1 ? 'Stok habis / sudah terjual' : 'Melebihi stok ('.$product->stock.' unit)' }}
                                                </span>
                                            @endunless
                                        </div>
                                    </div>

                                    <p class="text-[16px] font-semibold text-[#1e293b] md:w-[140px] md:shrink-0">
                                        <span class="text-[13px] font-normal text-[#64748b] md:hidden">Harga: </span>Rp{{ number_format($item->unit_price, 0, ',', '.') }}
                                        @if ($item->unit_price < (int) $product->price)
                                            <span class="block text-[12px] font-normal text-[#94a3b8] line-through">Rp{{ number_format((int) $product->price, 0, ',', '.') }}</span>
                                        @endif
                                    </p>

                                    <div class="flex items-center gap-2 md:w-[128px] md:shrink-0">
                                        <form method="POST" action="{{ route('cart.update', $item) }}" data-cart-qty-form>
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" name="quantity" value="{{ $item->quantity - 1 }}" data-cart-qty-step="-1" aria-label="Kurangi jumlah" @disabled($item->quantity <= 1) class="flex size-8 items-center justify-center rounded-[8px] border border-[#e2e8f0] bg-[#f8fafc] text-[18px] font-semibold text-[#1e293b] transition hover:border-[#00523f] disabled:cursor-not-allowed disabled:opacity-40">−</button>
                                        </form>
                                        <span class="flex h-8 w-12 items-center justify-center rounded-[8px] border border-[#e2e8f0] bg-[#f8fafc] text-[14px] font-semibold text-[#1e293b]" data-cart-qty>{{ $item->quantity }}</span>
                                        <form method="POST" action="{{ route('cart.update', $item) }}" data-cart-qty-form>
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" name="quantity" value="{{ $item->quantity + 1 }}" data-cart-qty-step="1" data-stock="{{ $product->stock }}" aria-label="Tambah jumlah" @disabled($item->quantity >= $product->stock) class="flex size-8 items-center justify-center rounded-[8px] border border-[#e2e8f0] bg-[#f8fafc] text-[18px] font-semibold text-[#1e293b] transition hover:border-[#00523f] disabled:cursor-not-allowed disabled:opacity-40">+</button>
                                        </form>
                                    </div>

                                    <p class="font-['Poppins'] text-[18px] font-bold text-[#00523f] md:w-[140px] md:shrink-0" data-cart-line-total>Rp{{ number_format($lineTotal, 0, ',', '.') }}</p>

                                    <div class="flex md:w-[80px] md:shrink-0 md:justify-center">
                                        <form method="POST" action="{{ route('cart.destroy', $item) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" aria-label="Hapus {{ $product->product_name }} dari keranjang" title="Hapus" class="flex size-10 items-center justify-center rounded-[10px] border border-[#e2e8f0] bg-white text-[#dc2626] transition hover:border-[#dc2626] hover:bg-[#fef2f2]">
                                                <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12M9 7V4h6v3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            @endforeach
                        </section>
                    @endforeach
                </div>
            @endif
        </main>

        @if ($itemCount > 0)
            <form method="GET" action="{{ route('cart.checkout') }}" data-cart-checkout-form class="fixed inset-x-0 bottom-0 z-30 bg-white shadow-[0_-4px_8px_rgba(0,0,0,0.08)]">
                <div class="mx-auto flex max-w-[1440px] flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" data-cart-select-all class="size-5 cursor-pointer rounded-[6px] border-2 border-[#cbd5e1] text-[#00523f] focus:ring-[#00523f]">
                        <span class="text-[14px] font-semibold text-[#1e293b]">Pilih Semua • <span data-cart-selected-summary>0 produk dipilih (0 unit)</span></span>
                    </label>
                    <div class="flex items-center justify-between gap-4 sm:justify-end">
                        <p class="font-['Poppins'] text-[20px] font-bold text-[#00523f] sm:text-[24px]">Total: <span data-cart-total>Rp0</span></p>
                        <button type="submit" data-cart-checkout-button disabled class="rounded-[12px] bg-[#00523f] px-6 py-3 font-['Poppins'] text-[15px] font-bold text-white transition hover:bg-[#006c52] disabled:cursor-not-allowed disabled:opacity-50 sm:text-[16px]">Checkout Sekarang</button>
                    </div>
                </div>
            </form>
        @endif
    </body>
</html>
