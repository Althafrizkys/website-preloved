<div class="rounded-[24px] bg-white p-5 shadow-[0_20px_60px_rgba(25,40,52,0.18)] sm:p-8">
    <header class="flex items-center gap-4">
        <button type="button" data-nego-close aria-label="Tutup popup nego" class="grid size-11 shrink-0 place-items-center rounded-full bg-[#00523f] text-white transition hover:bg-[#006c52]">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-linecap="round" stroke-width="2.5"/>
            </svg>
        </button>
        <h2 class="font-['Poppins'] text-[22px] font-semibold tracking-[-0.03em] text-[#202b3d] sm:text-[30px]">Nego Harga Produk</h2>
    </header>

    <section class="mt-5 flex items-center gap-4 rounded-[18px] bg-[#f7f9fa] p-4 sm:mt-6 sm:gap-6 sm:p-6">
        @php($cover = $product->media->firstWhere('media_type', 'foto'))
        @if ($cover)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($cover->media_url) }}" alt="{{ $product->product_name }}" class="size-[88px] shrink-0 rounded-[14px] bg-white object-cover sm:size-[160px]">
        @else
            <div class="grid size-[88px] shrink-0 place-items-center rounded-[14px] bg-[#eceff2] text-[10px] text-[#718096] sm:size-[160px]">Foto belum tersedia</div>
        @endif
        <div class="min-w-0">
            <p class="line-clamp-2 font-['Poppins'] text-[16px] font-semibold leading-snug text-[#202b3d] sm:text-[25px]">{{ $product->product_name }}</p>
            <p class="mt-2 text-[12px] text-[#718096] sm:text-[18px]">
                Harga Awal:
                <span class="font-medium text-[#526170]">Rp{{ number_format((float) $product->price, 0, ',', '.') }}</span>
            </p>
        </div>
    </section>

    @if (session('success'))
        <p class="mt-5 rounded-[10px] bg-[#e6f7f1] px-4 py-3 text-[12px] font-medium text-[#00523f]">{{ session('success') }}</p>
    @endif

    @if ($agreedOffer)
        <p class="mt-5 rounded-[10px] bg-[#e6f4f0] px-4 py-3 text-[12px] text-[#236a5a]">
            Tawaran telah diterima. Harga yang disepakati: <strong>Rp{{ number_format((float) $agreedOffer->offered_price, 0, ',', '.') }}</strong>.
        </p>
    @elseif ($product->status !== 'tersedia')
        <p class="mt-5 rounded-[10px] bg-[#f0f3f6] px-4 py-3 text-[12px] text-[#526170]">Barang sudah terjual. Form tawaran tidak lagi tersedia.</p>
    @elseif ($negotiations->contains('status', 'menunggu'))
        <p class="mt-5 rounded-[10px] bg-[#fff4d8] px-4 py-3 text-[12px] text-[#7a5a00]">Tawaranmu sedang menunggu jawaban penjual. Kamu bisa mengirim tawaran baru setelah tawaran ini ditolak.</p>
    @elseif ($canOffer)
        <form method="POST" action="{{ route('negotiations.store', $product) }}" class="mt-6">
            @csrf
            <label for="offered_price" class="block text-[16px] font-semibold text-[#202b3d] sm:text-[20px]">Masukkan Penawaran Hargamu</label>
            <div class="mt-3 flex min-h-[64px] items-center gap-3 rounded-[12px] border-2 border-[#00523f] px-4 focus-within:ring-2 focus-within:ring-[#00b888]/30 sm:min-h-[92px] sm:px-7">
                <span class="font-['Poppins'] text-[18px] font-semibold text-[#00523f] sm:text-[26px]">Rp</span>
                <input
                    id="offered_price"
                    name="offered_price"
                    type="number"
                    inputmode="numeric"
                    min="{{ $minimumOffer }}"
                    max="{{ (int) $product->price }}"
                    step="1"
                    value="{{ old('offered_price') }}"
                    data-offer-price
                    data-product-price="{{ (int) $product->price }}"
                    data-minimum-offer="{{ $minimumOffer }}"
                    required
                    placeholder="Contoh: 120.000"
                    class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[17px] text-[#202b3d] outline-none placeholder:text-[#87929e] focus:ring-0 sm:text-[23px]"
                >
            </div>
            @error('offered_price')
                <p class="mt-2 text-[12px] font-medium text-[#a33c3c]">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-[11px] text-[#718096]">Tawaran minimal Rp{{ number_format($minimumOffer, 0, ',', '.') }} dan tidak boleh melebihi harga barang.</p>

            <div class="mt-3 flex flex-wrap gap-2.5">
                @foreach ([10, 15, 20] as $discount)
                    <button type="button" data-offer-discount="{{ $discount }}" class="rounded-full bg-[#d9f8e8] px-4 py-2.5 text-[13px] font-semibold text-[#00523f] transition hover:bg-[#c4f2da] sm:px-6 sm:py-3 sm:text-[16px]">[-{{ $discount }}%]</button>
                @endforeach
            </div>
            <p class="mt-4 text-[12px] leading-5 text-[#718096] sm:text-[15px]">Tawaran akan dikirim langsung ke penjual. Fitur chat penuh akan tersedia pada fase berikutnya.</p>

            <button type="submit" class="mt-6 min-h-[60px] w-full rounded-[12px] bg-[#00523f] px-5 text-[17px] font-semibold text-white transition hover:bg-[#006c52] sm:mt-8 sm:min-h-[82px] sm:text-[22px]">Kirim Penawaran Nego</button>
        </form>
    @endif

    @include('negotiations._timeline', [
        'conversation' => $conversation,
        'negotiations' => $negotiations,
        'product' => $product,
        'canRespond' => false,
    ])
</div>
