@php
    $me = auth()->user();
    $iAmSeller = (int) $active->seller_id === (int) $me->user_id;
    $other = $iAmSeller ? $active->buyer : $active->seller;
    $product = $active->product;
    $isWishlistConversation = $active->wishlist_request_id !== null;
    $wishlist = $active->wishlistRequest;
    $photo = $product?->media->first();
    $lastId = $active->messages->max('message_id') ?? 0;
    $latestOffer = $active->negotiations->last();
    $latestWishlistProduct = $latestOffer?->product;
    $latestWishlistPhoto = $latestWishlistProduct?->media->first();
    $latestWishlistItemName = $latestOffer?->offered_item_name ?? $latestWishlistProduct?->product_name;
    $offerState = $latestOffer ? $latestOffer->negotiation_id.':'.$latestOffer->status : '';
    $latestOfferPriceLabel = match ($latestOffer?->status) {
        'menunggu' => 'Harga ditawarkan',
        'diterima' => 'Harga disepakati',
        'ditolak' => 'Penawaran ditolak',
        default => 'Harga penawaran',
    };
    $wishlistIsOpen = $wishlist?->status === 'aktif' && $wishlist->expired_at?->isFuture();
@endphp
<section class="flex min-h-0 flex-1 flex-col" aria-label="Percakapan">
    @if (session('success'))
        <p role="status" class="border-b border-[#bfe6d9] bg-[#edf7f4] px-5 py-2.5 text-[12px] font-medium text-[#00523f]">{{ session('success') }}</p>
    @endif
    <header class="flex items-center justify-between gap-3 border-b border-[#e7ebee] px-5 py-4">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('chat.index') }}" class="mr-1 text-[#236a5a] lg:hidden" aria-label="Kembali ke daftar pesan">‹</a>
            @include('chat._avatar', ['user' => $other, 'size' => 'size-12'])
            <div class="min-w-0">
                <p class="truncate font-['Poppins'] text-[16px] font-semibold text-[#263746]">{{ $other?->name ?? 'Pengguna' }}</p>
                <p class="mt-0.5 text-[11px]">
                    @if ($other?->campus)
                        <span class="rounded-full bg-[#e6f4f0] px-2.5 py-0.5 font-medium text-[#236a5a]">{{ $other->campus }} · {{ $isWishlistConversation ? ($iAmSeller ? 'Pemilik wishlist' : 'Calon penjual') : ($iAmSeller ? 'Pembeli' : 'Penjual') }}</span>
                    @else
                        <span class="text-[#718096]">{{ $isWishlistConversation ? ($iAmSeller ? 'Pemilik wishlist' : 'Calon penjual') : ($iAmSeller ? 'Pembeli' : 'Penjual') }}</span>
                    @endif
                </p>
            </div>
        </div>
    </header>

    <div class="flex items-center justify-between gap-3 border-b border-[#e7ebee] bg-[#f5f7f7] px-5 py-3">
        <div class="flex min-w-0 items-center gap-3">
            @if ($isWishlistConversation && $latestWishlistPhoto)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($latestWishlistPhoto->media_url) }}" alt="{{ $latestWishlistProduct->product_name }}" class="size-[52px] shrink-0 rounded-[8px] object-cover">
            @elseif ($isWishlistConversation && $wishlist?->image)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($wishlist->image) }}" alt="{{ $wishlist->item_name }}" class="size-[52px] shrink-0 rounded-[8px] object-cover">
            @elseif (! $isWishlistConversation && $photo)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->media_url) }}" alt="{{ $product->product_name }}" class="size-[52px] shrink-0 rounded-[8px] object-cover">
            @else
                <span class="size-[52px] shrink-0 rounded-[8px] bg-[#e1e6e9]" aria-hidden="true"></span>
            @endif
            <div class="min-w-0">
                @if ($isWishlistConversation && $latestOffer)
                    <p class="truncate text-[10px] text-[#718096]">Wishlist: {{ $wishlist?->item_name }} · Budget Rp{{ number_format((float) $wishlist?->budget, 0, ',', '.') }}</p>
                    <p class="truncate text-[15px] font-medium text-[#00523f]">{{ $latestWishlistItemName }}</p>
                    <p class="text-[13px] font-semibold text-[#263746]">{{ $latestOfferPriceLabel }} · Rp{{ number_format((float) $latestOffer->offered_price, 0, ',', '.') }}</p>
                @elseif ($isWishlistConversation)
                    <p class="text-[10px] text-[#718096]">Permintaan wishlist</p>
                    <p class="truncate text-[15px] font-medium text-[#00523f]">{{ $wishlist?->item_name }}</p>
                    <p class="text-[13px] font-semibold text-[#263746]">Budget Rp{{ number_format((float) $wishlist?->budget, 0, ',', '.') }}</p>
                @else
                    <p class="text-[10px] text-[#718096]">Anda sedang membahas produk ini</p>
                    <p class="truncate text-[15px] font-medium text-[#00523f]">{{ $product?->product_name }}</p>
                    <p class="text-[13px] font-semibold text-[#263746]">Rp{{ number_format((float) $product?->price, 0, ',', '.') }}</p>
                @endif
            </div>
        </div>
        @if (! $isWishlistConversation && $product)
            <a href="{{ route('products.show', $product) }}" class="shrink-0 text-[12px] font-semibold text-[#00523f] hover:underline">Lihat produk ›</a>
        @endif
    </div>

    <div data-chat-thread
         data-messages-url="{{ route('chat.messages', $active) }}"
         data-store-url="{{ route('chat.store', $active) }}"
         data-me="{{ $me->user_id }}"
         data-last-id="{{ $lastId }}"
         data-offer-state="{{ $isWishlistConversation ? $offerState : '' }}"
         data-watch-offers="{{ $isWishlistConversation ? 'true' : 'false' }}"
         class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-[#f7f9fa] px-5 py-5">
        <p class="text-center text-[11px] text-[#718096]">
            @if ($isWishlistConversation)
                Percakapan untuk wishlist {{ $wishlist?->item_name }}.<br>Diskusikan detail barang sebelum menyepakati transaksi.
            @else
                Percakapan dengan {{ $other?->name ?? 'Pengguna' }} dimulai.<br>Tanyakan ketersediaan dan detail produk sebelum membeli.
            @endif
        </p>

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
                        $offerProduct = $offer->product ?? $product;
                        $offerItemName = $offer->offered_item_name ?? $offerProduct?->product_name ?? $wishlist?->item_name;
                        $statusLabel = match ($offer->status) {
                            'menunggu' => $mine ? 'Penawaran Terkirim' : 'Menunggu jawaban Anda',
                            'diterima' => $isWishlistConversation ? 'Disepakati' : 'Diterima',
                            'ditolak' => 'Ditolak',
                            default => 'Tidak aktif',
                        };
                        $offerPriceLabel = match ($offer->status) {
                            'menunggu' => 'Harga yang ditawarkan',
                            'diterima' => 'Harga disepakati',
                            'ditolak' => 'Harga penawaran yang ditolak',
                            default => 'Harga penawaran',
                        };
                    @endphp
                    <div class="w-full max-w-[75%]" data-offer-id="{{ $offer->negotiation_id }}">
                        <div class="rounded-[14px] border border-[#e1e6e9] bg-white p-3">
                            <div class="rounded-[10px] bg-[#f0faf6] p-4">
                                <p class="text-[12px] font-semibold text-[#00523f]">{{ $isWishlistConversation ? 'Penawaran barang wishlist' : 'Penawaran harga' }}</p>
                                @if ($isWishlistConversation)
                                    <p class="mt-1 text-[13px] font-semibold text-[#263746]">{{ $offerItemName }}</p>
                                    @if ($offerProduct)
                                        <p class="mt-2 text-[10px] text-[#718096]">Harga barang saat ditawarkan: <span class="line-through">Rp{{ number_format((float) $offerProduct->price, 0, ',', '.') }}</span></p>
                                    @else
                                        <p class="mt-2 text-[10px] text-[#718096]">Budget wishlist: Rp{{ number_format((float) $wishlist?->budget, 0, ',', '.') }}</p>
                                    @endif
                                    <p class="mt-1 text-[13px] font-semibold text-[#00523f]">{{ $offerPriceLabel }} · Rp{{ number_format((float) $offer->offered_price, 0, ',', '.') }}</p>
                                @else
                                    <p class="mt-2 flex items-baseline gap-2">
                                        @if ($offerProduct)
                                            <span class="text-[12px] text-[#87929e] line-through">Rp{{ number_format((float) $offerProduct->price, 0, ',', '.') }}</span>
                                        @endif
                                        <span class="text-[20px] font-bold text-[#00523f]">Rp{{ number_format((float) $offer->offered_price, 0, ',', '.') }}</span>
                                    </p>
                                @endif
                                <div class="mt-3 border-t border-[#d8ebe3] pt-3">
                                    @if ($isWishlistConversation && ! $iAmSeller && $offer->status === 'menunggu')
                                        <div class="flex flex-wrap gap-2">
                                            @if ($wishlistIsOpen && (($offer->product_id === null && $offer->offered_item_name) || ($offerProduct && $offerProduct->status === 'tersedia' && $offerProduct->stock > 0)))
                                                <form method="POST" action="{{ route('wishlist-deals.accept', $offer) }}">
                                                    @csrf
                                                    <button type="submit" class="rounded-[8px] bg-[#00523f] px-5 py-1.5 text-[12px] font-semibold text-white shadow hover:bg-[#006c52]">Setujui &amp; lanjut transaksi</button>
                                                </form>
                                            @else
                                                <p class="self-center text-[11px] text-[#718096]">Wishlist atau barang ini sudah tidak aktif.</p>
                                            @endif
                                            <form method="POST" action="{{ route('wishlist-deals.reject', $offer) }}">
                                                @csrf
                                                <button type="submit" class="rounded-[8px] bg-white px-5 py-1.5 text-[12px] font-semibold text-[#c53030] shadow hover:bg-[#f7f9fa]">Tolak</button>
                                            </form>
                                        </div>
                                    @elseif (! $isWishlistConversation && $iAmSeller && $offer->status === 'menunggu' && $product?->status === 'tersedia')
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
                                        @if ($isWishlistConversation && ! $iAmSeller && $offer->status === 'diterima')
                                            <a href="{{ $offer->product_id ? route('transactions.create', ['product' => $offerProduct, 'negotiation_id' => $offer]) : route('transactions.create-wishlist', $offer) }}" class="mt-2 inline-flex rounded-[8px] bg-[#00523f] px-4 py-2 text-[12px] font-semibold text-white hover:bg-[#006c52]">Lanjut ke checkout COD</a>
                                        @endif
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

    @if ($isWishlistConversation && $iAmSeller)
        <section class="border-t border-[#e7ebee] bg-white px-5 py-3" aria-label="Tawarkan barang untuk wishlist">
            @if (! $wishlistIsOpen)
                <p class="text-[12px] text-[#718096]">Wishlist ini sudah tidak aktif.</p>
            @elseif ($active->negotiations->contains('status', 'menunggu'))
                <p class="text-[12px] text-[#718096]">Tawaranmu sedang menunggu jawaban pemilik wishlist.</p>
            @elseif ($active->negotiations->contains('status', 'diterima'))
                <p class="text-[12px] text-[#718096]">Kesepakatan sudah dibuat untuk wishlist ini.</p>
            @else
                <form method="POST" action="{{ route('wishlist-deals.store', $active) }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <label class="min-w-[180px] flex-1 text-[11px] font-medium text-[#526170]">
                        Nama barang yang ditawarkan
                        <input type="text" value="{{ $wishlist?->item_name }}" readonly class="mt-1 h-10 w-full rounded-[8px] border border-[#e1e6e9] bg-[#f7f9fa] px-3 text-[13px] text-[#202b3d]">
                    </label>
                    <label class="w-40 text-[11px] font-medium text-[#526170]">
                        Harga yang ditawarkan (Rp)
                        <input type="number" name="offered_price" min="1" max="9999999999" step="1" required value="{{ old('offered_price', (int) $wishlist?->budget) }}" class="mt-1 h-10 w-full rounded-[8px] border border-[#e1e6e9] px-3 text-[13px] text-[#202b3d]">
                    </label>
                    <button type="submit" class="h-10 rounded-[8px] bg-[#00523f] px-4 text-[12px] font-semibold text-white hover:bg-[#006c52]">Kirim penawaran</button>
                </form>
                @error('offered_price')
                    <p class="mt-1 text-[12px] text-[#c53030]" role="alert">{{ $message }}</p>
                @enderror
            @endif
        </section>
    @endif

    <form data-chat-form action="{{ route('chat.store', $active) }}" method="POST" class="bg-[#00523f] px-5 py-4">
        @csrf
        <div class="flex items-center gap-3">
            <input type="text" name="message_text" maxlength="1000" autocomplete="off" required placeholder="Tulis pesan..." class="min-h-11 flex-1 rounded-[8px] border-0 bg-white px-4 text-[14px] text-[#202b3d] placeholder:text-[#9aa5ae] focus:outline-none focus:ring-2 focus:ring-[#8fd0bb]">
            <button type="submit" class="min-h-11 rounded-[8px] bg-[#1a8a68] px-6 text-[13px] font-semibold text-white hover:bg-[#1f9d78]">Kirim</button>
        </div>
    </form>
</section>