<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Edit Barang — Seklov</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f7f9fa] font-['Inter'] text-[#202b3d]">
        <header class="px-4 pt-4 sm:px-6 sm:pt-6">
            <x-navbar />
        </header>

        <main class="mx-auto max-w-[1320px] px-4 pb-14 pt-5 sm:px-6 sm:pt-7">
            <nav class="mb-5 text-[13px] text-[#718096]" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-[#00523f]">Beranda</a>
                <span class="px-2">›</span>
                <a href="{{ route('products.show', $product) }}" class="hover:text-[#00523f]">Produk Saya</a>
                <span class="px-2">›</span>
                <span class="font-medium text-[#00523f]">Edit Produk</span>
            </nav>

            <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="font-['Poppins'] text-[28px] leading-tight font-semibold tracking-[-0.035em] sm:text-[34px]">Edit Produk</h1>
                    <p class="mt-2 text-[14px] text-[#718096] sm:text-[15px]">Perbarui detail produk dan lihat pratinjaunya sebelum disimpan.</p>
                </div>
                <p class="text-[12px] text-[#718096]">Perubahan belum dipublikasikan.</p>
            </div>

            @if (session('success'))
                <p class="mb-5 rounded-[10px] bg-[#e6f7f1] px-4 py-3 text-[14px] text-[#00523f]">{{ session('success') }}</p>
            @endif

            <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)] lg:gap-8">
                <section class="rounded-[14px] border border-[#e1e6e9] bg-white p-5 shadow-[0_3px_12px_rgba(22,34,48,0.04)] sm:p-6">
                    <h2 class="font-['Poppins'] text-[21px] font-semibold tracking-[-0.025em]">Perbarui Detail Produk</h2>
                    <p class="mt-1 text-[13px] text-[#718096]">Detail yang lengkap membantu produk lebih cepat terjual.</p>
                    @include('products._form')

                    <div class="mt-7 border-t border-[#e7ebee] pt-5">
                        <h3 class="text-[14px] font-semibold text-[#263746]">Kelola postingan</h3>
                        <div class="mt-3 flex flex-wrap gap-3">
                            @if ($product->status === 'tersedia')
                                <form method="POST" action="{{ route('products.status', $product) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-[9px] border border-[#00523f] px-4 py-2.5 text-[13px] font-semibold text-[#00523f] hover:bg-[#edf7f4]">Tandai terjual</button>
                                </form>
                            @else
                                <span class="rounded-[9px] bg-[#edf7f4] px-4 py-2.5 text-[13px] font-semibold text-[#00523f]">Barang sudah terjual</span>
                            @endif
                            <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Hapus postingan barang ini? Tindakan ini tidak dapat dibatalkan.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-[9px] border border-[#d6a0a0] px-4 py-2.5 text-[13px] font-semibold text-[#a33c3c] hover:bg-[#fff3f3]">Hapus postingan</button>
                            </form>
                        </div>
                    </div>
                </section>

                <aside class="lg:sticky lg:top-6">
                    <div class="rounded-[14px] border border-[#e1e6e9] bg-white p-5 shadow-[0_3px_12px_rgba(22,34,48,0.04)] sm:p-6">
                        <span class="inline-flex items-center gap-2 rounded-full bg-[#edf7f4] px-3 py-1.5 text-[12px] font-semibold text-[#00523f]">
                            <span class="size-1.5 rounded-full bg-[#00b888]"></span> Live Preview
                        </span>
                        <div class="mx-auto mt-4 w-full max-w-[300px] overflow-hidden rounded-[10px] bg-[#f0f1ef]">
                            @php($cover = $product->media->firstWhere('media_type', 'foto'))
                            <img data-live-image src="{{ $cover ? \Illuminate\Support\Facades\Storage::disk('public')->url($cover->media_url) : '' }}" alt="Pratinjau foto produk" class="{{ $cover ? '' : 'hidden' }} h-36 w-full object-contain sm:h-40">
                            <div data-live-placeholder class="{{ $cover ? 'hidden' : '' }} flex h-36 items-center justify-center text-[14px] text-[#8a969d] sm:h-40">Foto barang akan tampil di sini</div>
                        </div>
                        <div data-live-photo-thumbnails role="group" aria-label="Pilih foto pratinjau" class="mx-auto mt-2 hidden max-w-[300px] gap-2 overflow-x-auto py-1"></div>
                        @php($previewVideo = $product->media->firstWhere('media_type', 'video'))
                        <video data-live-video @if ($previewVideo) src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($previewVideo->media_url) }}" @endif controls preload="metadata" class="mt-3 max-h-40 w-full rounded-[8px] bg-black {{ $previewVideo ? '' : 'hidden' }}"></video>
                        <div class="mt-5">
                            <p data-live-name data-empty-text="Nama barangmu" class="font-['Poppins'] text-[21px] font-semibold leading-snug text-[#202b3d]">{{ $product->product_name }}</p>
                            <p data-live-price data-empty-text="Rp 0" class="mt-2 text-[24px] font-bold text-[#00523f]">Rp {{ number_format((float) $product->price, 0, ',', '.') }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span data-live-condition data-empty-text="Kondisi barang" class="rounded-full bg-[#edf7f4] px-3 py-1 text-[12px] font-medium text-[#00523f]">{{ $product->condition }}</span>
                                <span data-live-category data-empty-text="Kategori" class="rounded-full bg-[#f0f3f6] px-3 py-1 text-[12px] font-medium text-[#526170]">{{ $product->category->category_name }}</span>
                                <span data-live-negotiable data-empty-text="Harga pas" class="rounded-full bg-[#edf7f4] px-3 py-1 text-[12px] font-medium text-[#00523f]">{{ $product->is_negotiable ? 'Bisa nego' : 'Harga pas' }}</span>
                            </div>
                            <p data-live-stock data-empty-text="Stok belum ditentukan" class="mt-3 text-[12px] font-semibold text-[#526170]">Stok tersedia: {{ $product->stock }} unit</p>
                            <div class="mt-3 space-y-1 text-[12px] text-[#526170]">
                                <p>Ukuran: <span data-live-size data-empty-text="Belum diisi">{{ $product->size ?: 'Belum diisi' }}</span></p>
                                <p>Lama pemakaian: <span data-live-usage_period data-empty-text="Belum diisi">{{ $product->usage_period ?: 'Belum diisi' }}</span></p>
                                <p>Kekurangan: <span data-live-defects data-empty-text="Tidak ada">{{ $product->defects ?: 'Tidak ada' }}</span></p>
                            </div>
                            <div class="mt-5 rounded-[10px] bg-[#f7f9fa] p-4">
                                <p class="text-[12px] font-semibold text-[#263746]">Deskripsi produk</p>
                                <p data-live-description data-empty-text="Deskripsi barangmu akan muncul di sini agar calon pembeli mendapat informasi yang jelas." class="mt-2 whitespace-pre-line text-[13px] leading-5 text-[#718096]">{{ $product->description }}</p>
                            </div>
                            <p class="mt-4 text-center text-[12px] text-[#718096]">Pratinjau mengikuti perubahan detail produkmu.</p>
                        </div>
                    </div>
                </aside>
            </div>

            @include('negotiations._seller-conversations', [
                'product' => $product,
                'conversations' => $negotiationConversations,
            ])
        </main>
    </body>
</html>
