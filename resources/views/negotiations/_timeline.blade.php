<section class="mt-6" aria-label="Riwayat tawaran">
    <h3 class="font-['Poppins'] text-[15px] font-semibold text-[#202b3d]">Riwayat tawaran</h3>

    @if ($negotiations->isEmpty())
        <p class="mt-3 rounded-[10px] bg-[#f7f9fa] px-4 py-3 text-[12px] text-[#718096]">Belum ada riwayat tawaran.</p>
    @else
        <ol class="mt-3 space-y-3">
            @foreach ($negotiations as $offer)
                @php
                    $statusLabel = match ($offer->status) {
                        'menunggu' => 'Menunggu',
                        'diterima' => 'Diterima',
                        'ditolak' => 'Ditolak',
                        default => 'Tidak aktif',
                    };
                    $isBuyerOffer = $conversation && (int) $offer->sender_id === (int) $conversation->buyer_id;
                @endphp
                <li class="rounded-[10px] border border-[#e7ebee] bg-white p-3.5 sm:p-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="text-[12px] font-semibold text-[#263746]">
                                {{ $offer->sender?->name ?? 'Pengguna' }}
                                <span class="font-normal text-[#718096]">· {{ $isBuyerOffer ? 'Pembeli' : 'Penjual' }}</span>
                            </p>
                            <p class="mt-1 text-[17px] font-bold text-[#00523f]">Rp{{ number_format((float) $offer->offered_price, 0, ',', '.') }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-[10px] font-semibold {{ $offer->status === 'menunggu' ? 'bg-[#fff4d8] text-[#8a6500]' : ($offer->status === 'diterima' ? 'bg-[#e6f4f0] text-[#236a5a]' : 'bg-[#f0f3f6] text-[#526170]') }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                    <p class="mt-1 text-[10px] text-[#87929e]">{{ $offer->created_at?->format('d M Y, H:i') }}</p>

                    @if ($canRespond && $product->status === 'tersedia' && $offer->status === 'menunggu')
                        <div class="mt-3 flex flex-wrap gap-2 border-t border-[#edf0f2] pt-3">
                            <form method="POST" action="{{ route('negotiations.accept', $offer) }}">
                                @csrf
                                <button type="submit" class="rounded-[8px] bg-[#00523f] px-4 py-2 text-[11px] font-semibold text-white hover:bg-[#006c52]">Terima</button>
                            </form>
                            <form method="POST" action="{{ route('negotiations.reject', $offer) }}">
                                @csrf
                                <button type="submit" class="rounded-[8px] border border-[#dce3e6] px-4 py-2 text-[11px] font-semibold text-[#526170] hover:bg-[#f7f9fa]">Tolak</button>
                            </form>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    @endif
</section>
