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

            @if (session('error'))
                <div class="mb-6 rounded-[12px] bg-[#fff3f3] p-4 text-[13px] font-medium text-[#a33c3c]">
                    {{ session('error') }}
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
                                        <h3 class="text-[14px] font-semibold text-[#202b3d]">{{ $item->item_name_snapshot ?? $item->product?->product_name ?? 'Produk' }}</h3>
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

                {{-- Form Rating (Muncul setelah transaksi Selesai) --}}
                @if (in_array($transaction->transaction_status, ['selesai', 'completed']))
                    <div class="mt-6 rounded-[14px] border border-[#e7ebee] bg-[#f8faf9] p-5">
                        <div>
                            <h2 class="font-['Poppins'] text-[15px] font-bold text-[#202b3d]">
                                {{ $isSeller ? 'Beri Rating untuk Pembeli' : 'Beri Rating untuk Penjual' }}
                            </h2>
                            <p class="mt-0.5 text-[12px] text-[#718096]">
                                Bagikan pengalaman COD kamu dan dapatkan <span class="font-semibold text-[#00523f]">+25 Poin Reward</span> untuk penjual & pembeli!
                            </p>
                        </div>

                        @if ($hasRated)
                            <div class="mt-4 rounded-[10px] bg-[#e6f7f1] p-3.5 text-[13px] font-medium text-[#00523f]">
                                ✓ Terima kasih! Kamu sudah memberikan rating dan mengklaim poin reward untuk transaksi ini.
                            </div>
                        @else
                            <form action="{{ route('transactions.rating', $transaction->transaction_id) }}" method="POST" class="mt-4 space-y-4">
                                @csrf
                                <input type="hidden" name="rating" id="ratingInput" value="5" required>

                                <div class="py-2 text-center">
                                    <label class="mb-2 block text-[12px] font-semibold text-[#202b3d]">Penilaian Bintang</label>
                                    <div class="flex items-center justify-center gap-2" id="starContainer">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <button type="button"
                                                data-star="{{ $i }}"
                                                class="star-btn cursor-pointer text-[#fbbf24] transition-transform duration-150 hover:scale-110 focus:outline-none"
                                                aria-label="Beri {{ $i }} bintang">
                                                <svg class="size-9 fill-current" viewBox="0 0 24 24">
                                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                                </svg>
                                            </button>
                                        @endfor
                                    </div>
                                    <span id="ratingLabel" class="mt-1.5 inline-block text-[13px] font-semibold text-[#f59e0b]">
                                        Sangat Baik & Ramah
                                    </span>
                                </div>

                                <div>
                                    <label for="review" class="mb-1.5 block text-[12px] font-semibold text-[#202b3d]">Ulasan Singkat (Opsional)</label>
                                    <textarea id="review" name="review" rows="2" placeholder="Contoh: Barang sesuai deskripsi, COD tepat waktu di kampus!"
                                        class="w-full rounded-[10px] border border-[#dce3e6] bg-white px-3.5 py-2.5 text-[13px] text-[#202b3d] focus:border-[#00523f] focus:outline-none"></textarea>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit"
                                        class="rounded-[10px] bg-[#00523f] px-5 py-2.5 text-[13px] font-semibold text-white transition hover:bg-[#003d2e]">
                                        Kirim Rating & Klaim Poin
                                    </button>
                                </div>
                            </form>

                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    const stars = document.querySelectorAll('.star-btn');
                                    const ratingInput = document.getElementById('ratingInput');
                                    const ratingLabel = document.getElementById('ratingLabel');

                                    const labels = {
                                        1: 'Sangat Buruk',
                                        2: 'Kurang Memuaskan',
                                        3: 'Cukup Baik',
                                        4: 'Baik & Lancar',
                                        5: 'Sangat Baik & Ramah'
                                    };

                                    let selectedRating = 5;

                                    function updateStars(value) {
                                        stars.forEach((btn) => {
                                            const starVal = parseInt(btn.getAttribute('data-star'));
                                            if (starVal <= value) {
                                                btn.classList.remove('text-[#e2e8f0]');
                                                btn.classList.add('text-[#fbbf24]');
                                            } else {
                                                btn.classList.remove('text-[#fbbf24]');
                                                btn.classList.add('text-[#e2e8f0]');
                                            }
                                        });
                                        ratingLabel.textContent = labels[value] || '';
                                    }

                                    stars.forEach((btn) => {
                                        btn.addEventListener('mouseenter', () => {
                                            const hoverVal = parseInt(btn.getAttribute('data-star'));
                                            updateStars(hoverVal);
                                        });

                                        btn.addEventListener('click', () => {
                                            selectedRating = parseInt(btn.getAttribute('data-star'));
                                            ratingInput.value = selectedRating;
                                            updateStars(selectedRating);
                                        });
                                    });

                                    document.getElementById('starContainer').addEventListener('mouseleave', () => {
                                        updateStars(selectedRating);
                                    });

                                    updateStars(selectedRating);
                                });
                            </script>
                        @endif
                    </div>
                @endif

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

        {{-- Pop-up Modal Dapat Poin Reward --}}
        @if (session('reward_popup'))
            <div id="rewardModal"
                style="position: fixed; inset: 0; z-index: 9999; background-color: rgba(0, 0, 0, 0.55); display: flex; align-items: center; justify-content: center; padding: 16px;">
                <div style="background: #ffffff; border-radius: 20px; max-width: 420px; width: 100%; padding: 28px 24px; text-align: center; box-shadow: 0 16px 40px rgba(0,0,0,0.2);">
                    <div style="margin: 0 auto 16px; width: 64px; height: 64px; border-radius: 9999px; background-color: #e6f7f1; display: flex; align-items: center; justify-content: center; font-size: 32px;">
                        🏆
                    </div>
                    <h3 class="font-['Poppins']" style="font-size: 18px; font-weight: 700; color: #202b3d; margin: 0;">
                        {{ session('reward_popup.title') }}
                    </h3>
                    <div class="font-['Poppins']" style="margin: 12px 0; display: inline-block; padding: 6px 18px; border-radius: 9999px; background-color: #e6f7f1; color: #00523f; font-weight: 700; font-size: 16px;">
                        +{{ session('reward_popup.points') }} Poin Reward
                    </div>
                    <p style="font-size: 13px; line-height: 1.6; color: #526170; margin: 0 0 24px;">
                        {{ session('reward_popup.message') }}
                    </p>
                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <a href="{{ \Illuminate\Support\Facades\Route::has('profile.index') ? route('profile.index') : route('rewards.index') }}"
    style="flex: 1; padding: 10px 16px; border-radius: 10px; background-color: #00523f; color: #ffffff; font-size: 13px; font-weight: 600; text-decoration: none;">
    Lihat Poin Saya
</a>
                        <button type="button" onclick="document.getElementById('rewardModal').remove()"
                            style="flex: 1; padding: 10px 16px; border-radius: 10px; border: 1px solid #dce3e6; background-color: #ffffff; color: #526170; font-size: 13px; font-weight: 600; cursor: pointer;">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </body>
</html>