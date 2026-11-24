<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="font-['Poppins'] text-[21px] font-semibold tracking-[-0.025em] sm:text-[26px]">Permintaan Barang</h2>
        <p class="mt-1 text-[13px] text-[#718096]">{{ $isOwner ? 'Daftar barang yang sedang kamu cari' : 'Daftar barang yang sedang dicari oleh '.$user->name }}</p>
    </div>
    <div class="flex items-center gap-4">
        <p class="text-[13px] text-[#718096]">{{ $items->count() }} Barang Dicari</p>
        @if ($isOwner)
            <a href="{{ route('wishlist.create') }}" class="inline-flex h-10 items-center rounded-[10px] bg-[#00523f] px-5 text-[13px] font-semibold text-white transition hover:bg-[#006c52]">+ Tambah Wishlist</a>
        @endif
    </div>
</div>

@if ($items->isNotEmpty())
    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
        @foreach ($items as $wishlist)
            <x-wishlist-card :wishlist="$wishlist" :is-owner="$isOwner" />
        @endforeach
    </div>
@else
    <div class="rounded-[16px] border border-dashed border-[#d6dee2] bg-white px-5 py-12 text-center">
        <h3 class="font-['Poppins'] text-[18px] font-semibold text-[#263746]">Belum ada permintaan barang</h3>
    </div>
@endif