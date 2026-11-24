<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
    <h2 class="font-['Poppins'] text-[21px] font-semibold tracking-[-0.025em] sm:text-[26px]">Ulasan</h2>
    <p class="text-[13px] text-[#718096]">{{ $items->count() }} ulasan</p>
</div>

<div class="space-y-3">
    @forelse ($items as $rating)
        @php $reviewer = $reviewers[$rating->reviewer_id] ?? null; @endphp
        <article class="flex gap-4 rounded-[14px] border border-[#e5e9ed] bg-white p-4 shadow-[0_3px_10px_rgba(22,34,48,0.06)] sm:p-5">
            @if ($reviewer)
                <x-avatar :user="$reviewer" size="size-11" text="text-base" />
            @endif
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-[14px] font-semibold text-[#202b3d]">
                        @if ($reviewer)
                            <a href="{{ route('store.show', $reviewer) }}" class="hover:underline">{{ $reviewer->name }}</a>
                        @else
                            Pengguna
                        @endif
                    </p>
                    <span class="text-[12px] text-[#718096]">{{ \Illuminate\Support\Carbon::parse($rating->created_at)->format('d/m/Y') }}</span>
                </div>
                <div class="mt-1">
                    <x-star-rating :rating="$rating->stars" size="size-4" />
                </div>
                @if ($rating->review)
                    <p class="mt-2 break-words text-[14px] leading-6 text-[#526170]">{{ $rating->review }}</p>
                @endif
            </div>
        </article>
    @empty
        <div class="rounded-[16px] border border-dashed border-[#d6dee2] bg-white px-5 py-12 text-center">
            <h3 class="font-['Poppins'] text-[18px] font-semibold text-[#263746]">Belum ada ulasan</h3>
            <p class="mt-2 text-[14px] text-[#718096]">Ulasan muncul setelah transaksi selesai dan diberi rating.</p>
        </div>
    @endforelse
</div>