<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Checkout COD — Seklov</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f7f9fa] font-['Inter'] text-[#202b3d] antialiased">
        <header class="px-4 pt-4 sm:px-6 sm:pt-6">
            <x-navbar detail />
        </header>

        <main class="mx-auto max-w-[1200px] px-4 pb-16 pt-6 sm:px-6 sm:pt-8">
            <nav class="mb-5 text-[13px] text-[#718096]" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-[#00523f]">Beranda</a>
                <span class="px-2">›</span>
                <a href="{{ route('cart.index') }}" class="hover:text-[#00523f]">Keranjang</a>
                <span class="px-2">›</span>
                <span class="font-medium text-[#00523f]">Checkout COD</span>
            </nav>

            @if ($errors->any())
                <div class="mb-6 rounded-[12px] bg-[#fee2e2] p-4 text-[13px] text-[#991b1b]">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('cart.place-order') }}"
                data-checkout-form
                class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1.8fr)_380px]"
            >
                @csrf
                @foreach ($items as $item)
                    <input type="hidden" name="items[]" value="{{ $item->cart_item_id }}">
                @endforeach
                <input type="hidden" name="campus" id="selected_campus" value="{{ $defaultCampus }}">

                {{-- Kolom Kiri: Form Detail COD --}}
                <div class="space-y-5">
                    {{-- 1. Lokasi COD Kampus --}}
                    <section class="rounded-[16px] border border-[#e5e9ed] bg-white p-5 shadow-[0_2px_10px_rgba(22,34,48,0.03)] sm:p-6" aria-labelledby="heading-lokasi-cod">
                        <h2 id="heading-lokasi-cod" class="font-['Poppins'] text-[17px] font-bold text-[#202b3d] sm:text-[18px]">
                            1. Pilih Lokasi COD Kampus 📍
                        </h2>

                        {{-- Toggle Pilihan Kampus (UNSIKA / UBP) --}}
                        <div class="mt-4 flex gap-3">
                            <button
                                type="button"
                                data-campus-btn="UNSIKA"
                                class="flex-1 rounded-[12px] py-2.5 text-center text-[14px] font-bold transition {{ $defaultCampus === 'UNSIKA' ? 'bg-[#00523f] text-white' : 'bg-white border border-[#e5e9ed] text-[#526170] hover:border-[#00523f]' }}"
                            >
                                UNSIKA
                            </button>
                            <button
                                type="button"
                                data-campus-btn="UBP"
                                class="flex-1 rounded-[12px] py-2.5 text-center text-[14px] font-bold transition {{ $defaultCampus === 'UBP' ? 'bg-[#00523f] text-white' : 'bg-white border border-[#e5e9ed] text-[#526170] hover:border-[#00523f]' }}"
                            >
                                UBP
                            </button>
                        </div>

                        {{-- Titik Temu Spesifik --}}
                        <div class="mt-4">
                            <label for="meeting_point_id" class="block text-[13px] font-medium text-[#526170]">
                                Titik Temu Spesifik
                            </label>
                            <div class="relative mt-2">
                                <select
                                    id="meeting_point_id"
                                    name="meeting_point_id"
                                    required
                                    class="w-full appearance-none rounded-[12px] border border-[#e5e9ed] bg-white px-4 py-3 pr-10 text-[14px] text-[#202b3d] outline-none transition focus:border-[#00523f] focus:ring-1 focus:ring-[#00523f]"
                                >
                                    @foreach ($unsikaPoints as $point)
                                        <option
                                            value="{{ $point->meeting_point_id }}"
                                            data-campus="UNSIKA"
                                            {{ old('meeting_point_id') == $point->meeting_point_id ? 'selected' : ($defaultCampus === 'UNSIKA' && $loop->first ? 'selected' : '') }}
                                        >
                                            {{ $point->point_name }}
                                        </option>
                                    @endforeach

                                    @foreach ($ubpPoints as $point)
                                        <option
                                            value="{{ $point->meeting_point_id }}"
                                            data-campus="UBP"
                                            {{ old('meeting_point_id') == $point->meeting_point_id ? 'selected' : ($defaultCampus === 'UBP' && $loop->first ? 'selected' : '') }}
                                        >
                                            {{ $point->point_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-[#718096]">
                                    <svg class="size-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- Pesan / Catatan untuk Penjual --}}
                    <section class="rounded-[16px] border border-[#e5e9ed] bg-white p-5 shadow-[0_2px_10px_rgba(22,34,48,0.03)] sm:p-6" aria-labelledby="heading-catatan">
                        <h2 id="heading-catatan" class="font-['Poppins'] text-[17px] font-bold text-[#202b3d] sm:text-[18px]">
                            Pesan / Catatan untuk Penjual
                        </h2>
                        <div class="mt-3">
                            <textarea
                                id="buyer_notes"
                                name="buyer_notes"
                                rows="3"
                                placeholder="Contoh: Ketemu jam 13.00 WIB setelah kelas..."
                                class="w-full rounded-[12px] border border-[#e5e9ed] bg-white p-3.5 text-[14px] text-[#202b3d] outline-none transition placeholder:text-[#98a2ad] focus:border-[#00523f] focus:ring-1 focus:ring-[#00523f]"
                            >{{ old('buyer_notes') }}</textarea>
                        </div>
                    </section>

                    {{-- Produk Dipesan --}}
                    <section class="rounded-[16px] border border-[#e5e9ed] bg-white p-5 shadow-[0_2px_10px_rgba(22,34,48,0.03)] sm:p-6" aria-labelledby="heading-produk-dipesan">
                        <h2 id="heading-produk-dipesan" class="font-['Poppins'] text-[17px] font-bold text-[#202b3d] sm:text-[18px]">
                            Produk Dipesan
                        </h2>
                        <p class="mt-1 text-[12px] text-[#718096]">
                            Penjual: {{ $seller?->name ?? 'Penjual' }} ({{ $seller?->campus ?? 'Kampus' }}) · {{ $items->count() }} produk, {{ $totalUnits }} unit
                        </p>

                        <div class="mt-4">
                            <div class="hidden border-b border-[#edf0f2] pb-2 text-[12px] font-medium text-[#718096] sm:grid sm:grid-cols-[minmax(0,1.8fr)_120px_100px_120px] sm:items-center">
                                <span>Produk Dipesan</span>
                                <span class="text-center">Harga Satuan</span>
                                <span class="text-center">Jumlah</span>
                                <span class="text-right">Subtotal</span>
                            </div>

                            @foreach ($items as $item)
                                @php $cover = $item->product->media->first(); @endphp
                                <div class="flex flex-col gap-3 border-b border-[#f1f4f6] py-3 last:border-b-0 sm:grid sm:grid-cols-[minmax(0,1.8fr)_120px_100px_120px] sm:items-center">
                                    <div class="flex items-center gap-3">
                                        <div class="size-14 shrink-0 overflow-hidden rounded-[10px] bg-[#f0f1ef] sm:size-16">
                                            @if ($cover)
                                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($cover->media_url) }}" alt="{{ $item->product->product_name }}" class="size-full object-cover">
                                            @else
                                                <div class="flex size-full items-center justify-center text-[10px] text-[#8a969d]">Foto</div>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h3 class="line-clamp-2 text-[14px] font-semibold text-[#202b3d]">{{ $item->product->product_name }}</h3>
                                            <p class="mt-0.5 text-[11px] text-[#718096]">Stok tersedia: {{ $item->product->stock }} unit</p>
                                        </div>
                                    </div>
                                    <div class="text-[13px] text-[#202b3d] sm:text-center">
                                        <span class="text-[#718096] sm:hidden">Harga: </span>Rp{{ number_format($item->unit_price, 0, ',', '.') }}
                                    </div>
                                    <div class="text-[13px] font-semibold text-[#202b3d] sm:text-center">
                                        <span class="font-normal text-[#718096] sm:hidden">Jumlah: </span>{{ $item->quantity }}
                                    </div>
                                    <div class="text-[14px] font-bold text-[#00523f] sm:text-right">
                                        <span class="font-normal text-[#718096] sm:hidden">Subtotal: </span>Rp{{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ route('cart.index') }}" class="mt-3 inline-block text-[12px] font-medium text-[#00523f] underline">Ubah pilihan di keranjang</a>
                    </section>

                    {{-- 2. Metode Pembayaran --}}
                    <section class="rounded-[16px] border border-[#e5e9ed] bg-white p-5 shadow-[0_2px_10px_rgba(22,34,48,0.03)] sm:p-6" aria-labelledby="heading-metode-bayar">
                        <h2 id="heading-metode-bayar" class="font-['Poppins'] text-[17px] font-bold text-[#202b3d] sm:text-[18px]">
                            2. Metode Pembayaran 💳
                        </h2>

                        <div class="mt-4">
                            <div class="inline-flex items-center gap-3.5 rounded-[14px] bg-[#00523f] p-3.5 text-white shadow-sm sm:p-4">
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-[10px] bg-white text-[#00523f]">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="20" height="14" x="2" y="5" rx="2"/>
                                        <line x1="2" x2="22" y1="10" x2="22" y2="10"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="block font-bold text-[14px] leading-tight">COD</span>
                                    <span class="block text-[11px] text-white/80">Cash On Delivery</span>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- Kolom Kanan: Ringkasan Pembayaran (Sticky) --}}
                <aside class="lg:sticky lg:top-6">
                    <div class="rounded-[16px] border border-[#e5e9ed] bg-white p-6 shadow-[0_4px_16px_rgba(22,34,48,0.04)]">
                        <h2 class="font-['Poppins'] text-[18px] font-bold text-[#202b3d]">
                            Ringkasan Pembayaran
                        </h2>

                        <div class="mt-5 space-y-3 text-[14px]">
                            <div class="flex items-center justify-between text-[#526170]">
                                <span>Subtotal Produk</span>
                                <span data-summary-subtotal class="font-semibold text-[#202b3d]">
                                    Rp{{ number_format($totalPrice, 0, ',', '.') }}
                                </span>
                            </div>

                        </div>

                        <div class="my-4 border-t border-[#edf0f2]"></div>

                        <div class="flex items-center justify-between">
                            <span class="text-[15px] font-bold text-[#202b3d]">Total Bayar</span>
                            <span data-summary-total class="font-['Poppins'] text-[22px] font-bold text-[#00523f]">
                                Rp{{ number_format($totalPrice, 0, ',', '.') }}
                            </span>
                        </div>


                        {{-- Tombol Submit --}}
                        <button
                            type="submit"
                            class="mt-5 w-full rounded-[12px] bg-[#00523f] py-3.5 text-[15px] font-semibold text-white shadow-sm transition hover:bg-[#003d2e]"
                        >
                            Bayar &amp; Buat Pesanan
                        </button>
                    </div>
                </aside>
            </form>
        </main>

        <footer class="bg-[#00523f] px-5 py-8 text-white sm:px-8 sm:py-10">
            <div class="mx-auto flex max-w-[1200px] flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-['Poppins'] text-[20px] font-semibold">Seklov</p>
                    <p class="mt-1 text-[13px] text-white/70">Marketplace preloved mahasiswa Karawang.</p>
                </div>
                <p class="text-[12px] text-white/65">© {{ date('Y') }} Seklov. Untuk mahasiswa UNSIKA &amp; UBP.</p>
            </div>
        </footer>
    </body>
</html>
