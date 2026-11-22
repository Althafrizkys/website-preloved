@php
    $me = auth()->user();
    $iAmSeller = (int) $active->seller_id === (int) $me->user_id;
    $other = $iAmSeller ? $active->buyer : $active->seller;
    $product = $active->product;
    $photo = $product?->media->first();
    $lastId = $active->messages->max('message_id') ?? 0;
@endphp
<section class="flex min-h-0 flex-1 flex-col" aria-label="Percakapan">
    <header class="flex items-center justify-between gap-3 border-b border-[#e7ebee] px-5 py-4">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('chat.index') }}" class="mr-1 text-[#236a5a] lg:hidden" aria-label="Kembali ke daftar pesan">‹</a>
            @include('chat._avatar', ['user' => $other, 'size' => 'size-12'])
            <div class="min-w-0">
                <p class="truncate font-['Poppins'] text-[16px] font-semibold text-[#263746]">{{ $other?->name ?? 'Pengguna' }}</p>
                <p class="mt-0.5 text-[11px]">
                    @if ($other?->campus)
                        <span class="rounded-full bg-[#e6f4f0] px-2.5 py-0.5 font-medium text-[#236a5a]">{{ $other->campus }} · {{ $iAmSeller ? 'Pembeli' : 'Penjual' }}</span>
                    @else
                        <span class="text-[#718096]">{{ $iAmSeller ? 'Pembeli' : 'Penjual' }}</span>
                    @endif
                </p>
            </div>
        </div>
    </header>

    <div class="flex items-center justify-between gap-3 border-b border-[#e7ebee] bg-[#f5f7f7] px-5 py-3">
        <div class="flex min-w-0 items-center gap-3">
            @if ($photo)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->media_url) }}" alt="{{ $product->product_name }}" class="size-[52px] shrink-0 rounded-[8px] object-cover">
            @else
                <span class="size-[52px] shrink-0 rounded-[8px] bg-[#e1e6e9]" aria-hidden="true"></span>
            @endif
            <div class="min-w-0">
                <p class="text-[10px] text-[#718096]">Anda sedang membahas produk ini</p>
                <p class="truncate text-[15px] font-medium text-[#00523f]">{{ $product?->product_name }}</p>
                <p class="text-[13px] font-semibold text-[#263746]">Rp{{ number_format((float) $product?->price, 0, ',', '.') }}</p>
            </div>
        </div>
        @if ($product)
            <a href="{{ route('products.show', $product) }}" class="shrink-0 text-[12px] font-semibold text-[#00523f] hover:underline">Lihat produk ›</a>
        @endif
    </div>

    <div data-chat-thread
         data-messages-url="{{ route('chat.messages', $active) }}"
         data-store-url="{{ route('chat.store', $active) }}"
         data-me="{{ $me->user_id }}"
         data-last-id="{{ $lastId }}"
         class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-[#f7f9fa] px-5 py-5">
        <p class="text-center text-[11px] text-[#718096]">Percakapan dengan {{ $other?->name ?? 'Pengguna' }} dimulai.<br>Tanyakan ketersediaan dan detail produk sebelum membeli.</p>

        @foreach ($timeline as $entry)
            @php $mine = (int) $entry['item']->sender_id === (int) $me->user_id; @endphp
            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                @if ($entry['type'] === 'message')
                    <div class="max-w-[75%]" data-message-id="{{ $entry['item']->message_id }}">
                        <div class="rounded-[14px] border border-[#e1e6e9] bg-white px-4 py-3 text-[14px] text-[#202b3d] break-words whitespace-pre-line">{{ $entry['item']->message_text }}</div>
                        <p class="mt-1 text-[10px] text-[#87929e] {{ $mine ? 'text-right' : '' }}">{{ $entry['item']->sent_at?->format('H.i') }}</p>
                    </div>
                @else
                    @php
                        $offer = $entry['item'];
                        $statusLabel = match ($offer->status) {
                            'menunggu' => $mine ? 'Penawaran Terkirim' : 'Menunggu jawaban Anda',
                            'diterima' => 'Diterima',
                            'ditolak' => 'Ditolak',
                            default => 'Tidak aktif',
                        };
                    @endphp
                    <div class="w-full max-w-[75%]" data-offer-id="{{ $offer->negotiation_id }}">
                        <div class="rounded-[14px] border border-[#e1e6e9] bg-white p-3">
                            <div class="rounded-[10px] bg-[#f0faf6] p-4">
                                <p class="text-[12px] font-semibold text-[#00523f]">Penawaran harga</p>
                                <p class="mt-2 flex items-baseline gap-2">
                                    <span class="text-[12px] text-[#87929e] line-through">Rp{{ number_format((float) $product?->price, 0, ',', '.') }}</span>
                                    <span class="text-[20px] font-bold text-[#00523f]">Rp{{ number_format((float) $offer->offered_price, 0, ',', '.') }}</span>
                                </p>
                                <div class="mt-3 border-t border-[#d8ebe3] pt-3">
                                    @if ($iAmSeller && $offer->status === 'menunggu' && $product?->status === 'tersedia')
                                        <div class="flex gap-2">
                                            <form method="POST" action="{{ route('negotiations.accept', $offer) }}">
                                                @csrf
                                                <button type="submit" class="rounded-[8px] bg-white px-5 py-1.5 text-[12px] font-semibold text-[#00523f] shadow hover:bg-[#f7f9fa]">Terima</button>
                                            </form>
                                            <form method="POST" action="{{ route('negotiations.reject', $offer) }}">
                                                @csrf
                                                <button type="submit" class="rounded-[8px] bg-white px-5 py-1.5 text-[12px] font-semibold text-[#c53030] shadow hover:bg-[#f7f9fa]">Tolak</button>
                                            </form>
                                        </div>
                                    @else
                                        <p class="text-[12px] font-semibold text-[#00523f]">{{ $statusLabel }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <p class="mt-1 text-[10px] text-[#87929e] {{ $mine ? 'text-right' : '' }}">{{ $offer->created_at?->format('H.i') }}</p>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <form data-chat-form action="{{ route('chat.store', $active) }}" method="POST" class="bg-[#00523f] px-5 py-4">
        @csrf
        <div class="flex items-center gap-3">
            <input type="text" name="message_text" maxlength="1000" autocomplete="off" required placeholder="Tulis pesan..." class="min-h-11 flex-1 rounded-[8px] border-0 bg-white px-4 text-[14px] text-[#202b3d] placeholder:text-[#9aa5ae] focus:outline-none focus:ring-2 focus:ring-[#8fd0bb]">
            <button type="submit" class="min-h-11 rounded-[8px] bg-[#1a8a68] px-6 text-[13px] font-semibold text-white hover:bg-[#1f9d78]">Kirim</button>
        </div>
    </form>
</section>