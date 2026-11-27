@props(['wishlist', 'isOwner' => false, 'showUser' => false])

<article class="flex h-full flex-col overflow-hidden rounded-[14px] border border-[#e5e9ed] bg-white shadow-[0_3px_10px_rgba(22,34,48,0.06)]">
    <div class="relative aspect-[4/3] overflow-hidden bg-[#eceff2]">
        @if ($wishlist->image)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($wishlist->image) }}" alt="{{ $wishlist->item_name }}" class="size-full object-cover">
        @else
            <div class="flex size-full items-center justify-center bg-gradient-to-br from-[#eef1f3] to-[#e2e7e9] text-[#98a4a8]" aria-label="Foto belum tersedia">
                <svg class="size-12" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                    <path d="M8 11.5A3.5 3.5 0 0 1 11.5 8h25a3.5 3.5 0 0 1 3.5 3.5v25a3.5 3.5 0 0 1-3.5 3.5h-25A3.5 3.5 0 0 1 8 36.5v-25Z" stroke="currentColor" stroke-width="2.5"/>
                    <circle cx="18" cy="18" r="3" stroke="currentColor" stroke-width="2.5"/>
                    <path d="m10 33 9-9 6 6 4-4 9 9" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"/>
                </svg>
            </div>
        @endif

        @if ($isOwner && $wishlist->status !== 'aktif')
            <span class="absolute left-3 top-3 rounded-full bg-[#526170] px-3 py-1 text-[11px] font-semibold capitalize text-white">{{ $wishlist->status }}</span>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-3.5 sm:p-4">
        <p class="line-clamp-2 min-h-[42px] text-[14px] font-semibold leading-[21px] text-[#202b3d]">{{ $wishlist->item_name }}</p>

        <div class="mt-1.5 flex flex-wrap gap-1.5">
            @if ($wishlist->condition)
                <span class="rounded-full bg-[#00523f] px-3 py-1 text-[11px] font-semibold text-white">{{ $wishlist->condition }}</span>
            @endif
            <span class="rounded-full bg-[#f0f3f6] px-2.5 py-1 text-[10px] font-medium text-[#526170]">{{ $wishlist->category?->category_name ?? 'Lainnya' }}</span>
        </div>

        <p class="mt-2 text-[12px] text-[#718096]">Budget</p>
        <p class="text-[17px] font-bold text-[#00523f]">Rp {{ number_format((float) $wishlist->budget, 0, ',', '.') }}</p>

        <p class="mt-auto pt-3 text-[12px] text-[#718096]">Berlaku sampai {{ $wishlist->expired_at->format('d/m/Y') }}</p>

        @if ($showUser && $wishlist->user)
            <a href="{{ route('store.show', $wishlist->user) }}" class="mt-3 flex items-center gap-2.5 border-t border-[#eef1f3] pt-3 transition hover:opacity-80">
                <x-avatar :user="$wishlist->user" size="size-8" text="text-[13px]" />
                <span class="min-w-0">
                    <span class="block truncate text-[12px] font-semibold text-[#202b3d]">{{ $wishlist->user->name }}</span>
                    <span class="block text-[11px] text-[#718096]">{{ $wishlist->user->campus }} · Lihat profil</span>
                </span>
            </a>
            @if (! $isOwner && $wishlist->status === 'aktif' && $wishlist->expired_at?->isFuture())
                @auth
                    <form method="POST" action="{{ route('chat.wishlist.start', $wishlist) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-[10px] bg-[#00523f] px-4 text-[13px] font-semibold text-white transition hover:bg-[#006c52]">
                            <span aria-hidden="true">◉</span> Chat pemilik wishlist
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="mt-3 inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-[10px] bg-[#00523f] px-4 text-[13px] font-semibold text-white transition hover:bg-[#006c52]">
                        Chat pemilik wishlist
                    </a>
                @endauth
            @endif
        @endif

        @if ($isOwner)
            <div class="mt-3 flex items-center gap-4 border-t border-[#eef1f3] pt-3 text-[13px] font-medium">
                <a href="{{ route('wishlist.edit', $wishlist) }}" class="text-[#00523f] hover:underline">Edit</a>
                <form method="POST" action="{{ route('wishlist.destroy', $wishlist) }}" onsubmit="return confirm('Hapus permintaan barang ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-[#e53e3e] hover:underline">Hapus</button>
                </form>
            </div>
        @endif
    </div>
</article>