<section class="mt-7 rounded-[14px] border border-[#e1e6e9] bg-white p-5 shadow-[0_3px_12px_rgba(22,34,48,0.04)] sm:p-6" aria-labelledby="incoming-offers-title">
    <h2 id="incoming-offers-title" class="font-['Poppins'] text-[20px] font-semibold tracking-[-0.025em] text-[#202b3d]">Tawaran masuk</h2>
    <p class="mt-1 text-[12px] leading-5 text-[#718096]">Tinjau riwayat tawaran. Kamu hanya bisa menerima atau menolak tawaran yang masih menunggu.</p>

    @if ($conversations->isEmpty())
        <p class="mt-4 rounded-[10px] bg-[#f7f9fa] px-4 py-3 text-[12px] text-[#718096]">Belum ada tawaran untuk barang ini.</p>
    @else
        <div class="mt-5 space-y-5">
            @foreach ($conversations as $conversation)
                <article class="rounded-[12px] border border-[#e7ebee] p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-[13px] font-semibold text-[#263746]">{{ $conversation->buyer?->name ?? 'Pembeli' }}</h3>
                            @if ($conversation->buyer?->campus)
                                <p class="mt-1 text-[11px] text-[#718096]">{{ $conversation->buyer->campus }}</p>
                            @endif
                        </div>
                        <span class="text-[10px] text-[#87929e]">Percakapan #{{ $conversation->conversation_id }}</span>
                    </div>

                    @include('negotiations._timeline', [
                        'conversation' => $conversation,
                        'negotiations' => $conversation->negotiations,
                        'product' => $product,
                        'canRespond' => true,
                    ])
                </article>
            @endforeach
        </div>
    @endif
</section>
