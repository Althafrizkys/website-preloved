@php
    $me = auth()->user();
    $other = (int) $conversation->buyer_id === (int) $me->user_id ? $conversation->seller : $conversation->buyer;
    $last = $conversation->latestMessage;
    $isActive = $active && (int) $active->conversation_id === (int) $conversation->conversation_id;
@endphp
<a href="{{ route('chat.show', array_filter(['conversation' => $conversation, 'filter' => $filter !== 'semua' ? $filter : null, 'q' => $search !== '' ? $search : null])) }}"
   class="block rounded-[12px] border p-3 transition {{ $isActive ? 'border-[#bcdcd2] bg-[#eef7f4]' : 'border-transparent hover:bg-[#f7f9fa]' }}">
    <div class="flex items-center gap-3">
        @include('chat._avatar', ['user' => $other, 'size' => 'size-10'])
        <div class="min-w-0 flex-1">
            <p class="truncate text-[13px] font-semibold text-[#263746]">{{ $other?->name ?? 'Pengguna' }}</p>
            <p class="truncate text-[11px] font-medium text-[#236a5a]">{{ $conversation->product?->product_name ?? $conversation->wishlistRequest?->item_name ?? 'Percakapan' }}</p>
        </div>
    </div>
    <div class="mt-2 flex items-end justify-between gap-2">
        <p class="truncate text-[11px] text-[#718096]">
            @if ($last)
                {{ (int) $last->sender_id === (int) $me->user_id ? 'Anda: ' : '' }}{{ $last->message_text }}
            @else
                Belum ada pesan
            @endif
        </p>
        @if (($conversation->unread_count ?? 0) > 0 && ! $isActive)
            <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-[#00523f] text-[10px] font-bold text-white">{{ $conversation->unread_count > 9 ? '9+' : $conversation->unread_count }}</span>
        @endif
    </div>
    @if ($last)
        <p class="mt-1 text-[10px] text-[#87929e]">{{ $last->sent_at?->format('H.i') }}</p>
    @endif
</a>