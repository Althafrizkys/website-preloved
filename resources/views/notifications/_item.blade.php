@props(['notification'])

<div
    data-notification-item
    data-id="{{ $notification->notification_id }}"
    data-role="{{ $notification->effective_role }}"
    data-read="{{ $notification->is_read ? 'true' : 'false' }}"
    class="group relative flex items-center justify-between gap-3 rounded-[14px] bg-[#f4f6f5] p-3 transition hover:bg-[#ebf0ed]"
>
    @if (! $notification->is_read)
        <span data-unread-dot class="absolute right-2.5 top-2.5 size-2 rounded-full bg-[#00523f]"></span>
    @endif

    <div class="flex min-w-0 flex-1 items-center gap-3">
        <div class="size-12 shrink-0 overflow-hidden rounded-[10px] bg-[#e2e7e4]">
            @if ($notification->product_cover_url)
                <img src="{{ $notification->product_cover_url }}" alt="{{ $notification->product_name }}" class="size-full object-cover">
            @else
                <div class="flex size-full items-center justify-center text-[#718096]">
                    <svg class="size-6 text-[#8a9892]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m7.5 4.27 9 5.15M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                        <path d="m3.3 7 8.7 5 8.7-5M12 22V12"/>
                    </svg>
                </div>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            {{-- Judul event (konteks apa yang terjadi) --}}
            <h4 class="truncate text-[13px] font-semibold text-[#1c2c26]" title="{{ $notification->title }}">
                {{ $notification->title }}
            </h4>
            {{-- Pesan detail kontekstual --}}
            @if ($notification->message)
                <p class="mt-0.5 line-clamp-2 text-[11.5px] leading-snug text-[#4a5a52]">{{ $notification->message }}</p>
            @else
                {{-- Fallback: tampilkan nama produk dan harga --}}
                <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[12px]">
                    @if ($notification->display_original_price)
                        <span class="text-[#8e9d96] line-through">Rp {{ number_format($notification->display_original_price, 0, ',', '.') }}</span>
                    @endif
                    @if ($notification->display_offered_price)
                        <span class="font-bold text-[#00523f]">Rp {{ number_format($notification->display_offered_price, 0, ',', '.') }}</span>
                    @endif
                </div>
            @endif
            <p class="mt-1 text-[11px] text-[#8e9d96]">{{ $notification->time_ago }}</p>
        </div>
    </div>

    <a
        href="{{ $notification->target_url }}"
        data-chat-link
        data-notification-id="{{ $notification->notification_id }}"
        title="Buka tawaran"
        class="flex size-8 shrink-0 items-center justify-center rounded-full border border-[#cbd5e1] bg-white text-[#00523f] shadow-sm transition hover:border-[#00523f] hover:bg-[#edf7f4]"
    >
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
    </a>
</div>
