<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Detail Transaksi #{{ $transaction->transaction_id }} — Seklov</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f7f9fa] font-['Inter'] text-[#202b3d] antialiased">
        <header class="px-4 pt-4 sm:px-6 sm:pt-6">
            <x-navbar detail />
        </header>

        <main class="mx-auto max-w-[960px] px-4 pb-16 pt-6 sm:px-6 sm:pt-8">
            <nav class="mb-5 text-[13px] text-[#718096]" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-[#00523f]">Beranda</a>
                <span class="px-2">›</span>
                <a href="{{ route('transactions.index') }}" class="hover:text-[#00523f]">Daftar Transaksi</a>
                <span class="px-2">›</span>
                <span class="font-medium text-[#00523f]">Pesanan #{{ $transaction->transaction_id }}</span>
            </nav>

            @if (session('success'))
                <div class="mb-6 rounded-[12px] bg-[#e6f7f1] p-4 text-[13px] font-medium text-[#00523f]">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-[20px] border border-[#e5e9ed] bg-white p-6 shadow-[0_4px_16px_rgba(22,34,48,0.04)] sm:p-8">
                {{-- Header Transaksi --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#edf0f2] pb-5">
                    <div>
                        <span class="text-[12px] font-medium text-[#718096]">ID Pesanan: #{{ $transaction->transaction_id }}</span>
                        <h1 class="font-['Poppins'] text-[22px] font-bold text-[#202b3d]">
                            Detail Pesanan COD
                        </h1>
                        <p class="mt-1 text-[12px] text-[#718096]">
                            Dibuat pada {{ $transaction->created_at?->format('d M Y, H:i') }} WIB
                        </p>
                    </div>
                    <span class="rounded-full px-4 py-1.5 text-[12px] font-semibold {{ $transaction->status_badge_class }}">
                        {{ $transaction->status_label }}
                    </span>
                </div>

                {{-- Info COD Kampus --}}
                <div class="mt-6 rounded-[14px] bg-[#f8faf9] p-4 sm:p-5">
                    <h2 class="text-[14px] font-bold text-[#202b3d]">Lokasi COD Kampus 📍</h2>
                    <p class="mt-1.5 text-[14px] font-semibold text-[#00523f]">
                        {{ $transaction->meetingPoint?->campus }} — {{ $transaction->meetingPoint?->point_name }}
                    </p>
                    <p class="mt-1 text-[12px] text-[#718096]">
                        {{ $transaction->meetingPoint?->address_description }}
                    </p>

                    @if ($transaction->buyer_notes)
                        <div class="mt-4 border-t border-[#e7ebee] pt-3">
                            <span class="text-[12px] font-medium text-[#526170]">Catatan untuk Penjual:</span>
                            <p class="mt-1 text-[13px] italic text-[#202b3d]">"{{ $transaction->buyer_notes }}"</p>
                        </div>
                    @endif
                </div>

                {{-- Pihak Transaksi --}}
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-[12px] border border-[#e7ebee] p-4">
                        <span class="text-[11px] font-medium text-[#718096]">Pembeli</span>
                        <p class="mt-1 text-[14px] font-semibold text-[#202b3d]">{{ $transaction->buyer?->name }}</p>
                        <p class="text-[12px] text-[#718096]">{{ $transaction->buyer?->campus }} (Angkatan {{ $transaction->buyer?->cohort ?? '-' }})</p>
                    </div>
                    <div class="rounded-[12px] border border-[#e7ebee] p-4">
                        <span class="text-[11px] font-medium text-[#718096]">Penjual</span>
                        <p class="mt-1 text-[14px] font-semibold text-[#202b3d]">{{ $transaction->seller?->name }}</p>
                        <p class="text-[12px] text-[#718096]">{{ $transaction->seller?->campus }} (Angkatan {{ $transaction->seller?->cohort ?? '-' }})</p>
                    </div>
                </div>

                {{-- Rincian Produk --}}
                <div class="mt-6">
                    <h2 class="text-[14px] font-bold text-[#202b3d]">Produk Dipesan</h2>
                    <div class="mt-3 divide-y divide-[#edf0f2] rounded-[12px] border border-[#e7ebee]">
                        @foreach ($transaction->items as $item)
                            @php($cover = $item->product?->media->firstWhere('media_type', 'foto'))
                            <div class="flex items-center justify-between p-4">
                                <div class="flex items-center gap-3">
                                    <div class="size-14 shrink-0 overflow-hidden rounded-[8px] bg-[#f0f1ef]">
                                        @if ($cover)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($cover->media_url) }}" alt="" class="size-full object-cover">
                                        @endif
                                    </div>
                                    <div>
                                        <h3 class="text-[14px] font-semibold text-[#202b3d]">{{ $item->product?->product_name ?? 'Produk' }}</h3>
                                        <p class="text-[12px] text-[#718096]">
                                            Rp{{ number_format((float) $item->agreed_price, 0, ',', '.') }} × {{ $item->quantity }}
                                        </p>
                                    </div>
                                </div>
                                <span class="font-bold text-[#00523f]">
                                    Rp{{ number_format((float) $item->subtotal, 0, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Rincian Total --}}
                <div class="mt-6 border-t border-[#edf0f2] pt-4">
                    <div class="space-y-2 text-[13px] text-[#526170]">
                        <div class="flex justify-between">
                            <span>Subtotal Produk</span>
                            <span class="font-medium text-[#202b3d]">Rp{{ number_format((float) $transaction->subtotal_price, 0, ',', '.') }}</span>
                        </div>
                        
                        <div class="flex items-center justify-between border-t border-[#edf0f2] pt-3 text-[16px] font-bold text-[#202b3d]">
                            <span>Total Pembayaran (COD)</span>
                            <span class="font-['Poppins'] text-[20px] text-[#00523f]">
                                Rp{{ number_format((float) $transaction->total_price, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Tombol Aksi Transaksi --}}
                <div class="mt-8 flex flex-wrap items-center justify-end gap-3 border-t border-[#edf0f2] pt-5">
                    @if ($isSeller && $transaction->transaction_status === 'menunggu')
                        <form method="POST" action="{{ route('transactions.confirm', $transaction) }}">
                            @csrf
                            <button type="submit" class="rounded-[10px] bg-[#00523f] px-5 py-2.5 text-[13px] font-semibold text-white hover:bg-[#003d2e]">
                                Konfirmasi Jadwal COD
                            </button>
                        </form>
                    @endif

                    @if ($transaction->transaction_status === 'dijadwalkan')
                        <form method="POST" action="{{ route('transactions.complete', $transaction) }}" onsubmit="return confirm('Apakah transaksi COD telah selesai dan barang sudah diterima/diserahkan?')">
                            @csrf
                            <button type="submit" class="rounded-[10px] bg-[#00523f] px-5 py-2.5 text-[13px] font-semibold text-white hover:bg-[#003d2e]">
                                Tandai COD Selesai
                            </button>
                        </form>
                    @endif

                    @if ($transaction->transaction_status === 'menunggu')
                        <form method="POST" action="{{ route('transactions.cancel', $transaction) }}" onsubmit="return confirm('Yakin ingin membatalkan pesanan COD ini?')">
                            @csrf
                            <button type="submit" class="rounded-[10px] border border-[#d6a0a0] px-5 py-2.5 text-[13px] font-semibold text-[#a33c3c] hover:bg-[#fff3f3]">
                                Batalkan Pesanan
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('transactions.index') }}" class="rounded-[10px] border border-[#dce3e6] px-5 py-2.5 text-[13px] font-semibold text-[#526170] hover:bg-[#f7f9fa]">
                        Kembali ke Daftar Transaksi
                    </a>
                </div>
            </div>
        </main>
    </body>
</html>
