@php
    $me = (int) (auth()->user()?->user_id ?? 0);
    $field = 'min-h-11 rounded-[10px] border border-[#e1e6e9] bg-white px-3 text-[14px] text-[#526170] focus:border-[#00523f] focus:outline-none focus:ring-0';
    $hasFilter = $search !== '' || $selectedCategory !== '' || $selectedCondition !== '';
@endphp

<x-layouts.app title="Permintaan Barang">
    @if (session('success'))
        <div role="status" class="mb-4 rounded-[12px] border border-[#bfe6d9] bg-[#edf7f4] px-4 py-3 text-[13px] font-medium text-[#00523f]">{{ session('success') }}</div>
    @endif

    <section class="rounded-3xl bg-gradient-to-r from-[#00523F] to-[#10B98A] px-6 py-8 text-white shadow-[0_8px_24px_rgba(0,82,63,0.16)] sm:px-10">
        <h1 class="font-['Poppins'] text-[26px] font-semibold tracking-[-0.03em] sm:text-[34px]">Permintaan Barang</h1>
        <p class="mt-2 max-w-xl text-[14px] leading-6 text-white/90 sm:text-[16px]">Barang yang sedang dicari mahasiswa. Punya barangnya? Mulai chat dari kartu wishlist untuk menawarkan barangmu.</p>
        <a href="{{ auth()->check() ? route('wishlist.create') : route('login') }}" class="mt-5 inline-flex h-11 items-center rounded-xl bg-white px-5 text-[14px] font-bold text-[#00523F] shadow-sm transition hover:bg-[#e8fff7]">+ Minta Barang</a>
    </section>

    <form method="GET" action="{{ route('wishlist.index') }}" role="search" class="mt-5 flex flex-wrap items-center gap-2">
        <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama barang..." class="{{ $field }} min-w-0 flex-1 sm:max-w-xs">

        <select name="kategori" onchange="this.form.submit()" class="{{ $field }}" aria-label="Kategori">
            <option value="">Semua kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->category_id }}" @selected($selectedCategory === (string) $category->category_id)>{{ $category->category_name }}</option>
            @endforeach
        </select>

        <select name="kondisi" onchange="this.form.submit()" class="{{ $field }}" aria-label="Kondisi">
            <option value="">Semua kondisi</option>
            @foreach ($conditions as $condition)
                <option value="{{ $condition }}" @selected($selectedCondition === $condition)>{{ $condition }}</option>
            @endforeach
        </select>

        <button type="submit" class="min-h-11 rounded-[10px] bg-[#00523f] px-5 text-[14px] font-semibold text-white transition hover:bg-[#006c52]">Cari</button>

        @if ($hasFilter)
            <a href="{{ route('wishlist.index') }}" class="inline-flex min-h-11 items-center px-1 text-[14px] font-medium text-[#00523f] underline">Reset filter</a>
        @endif
    </form>

    <div class="mb-4 mt-6 flex flex-wrap items-end justify-between gap-2">
        <h2 class="font-['Poppins'] text-[21px] font-semibold tracking-[-0.025em] sm:text-[26px]">Sedang dicari</h2>
        <p class="text-[13px] text-[#718096]">{{ $wishlists->total() }} barang</p>
    </div>

    @if ($wishlists->isNotEmpty())
        <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($wishlists as $wishlist)
                <x-wishlist-card :wishlist="$wishlist" :is-owner="$me === (int) $wishlist->user_id" :show-user="true" />
            @endforeach
        </div>
        <div class="mt-8">{{ $wishlists->links() }}</div>
    @else
        <div class="rounded-[16px] border border-dashed border-[#d6dee2] bg-white px-5 py-12 text-center">
            <h3 class="font-['Poppins'] text-[18px] font-semibold text-[#263746]">Belum ada permintaan barang yang cocok</h3>
            <p class="mt-2 text-[14px] text-[#718096]">Coba ubah kata pencarian atau filter.</p>
        </div>
    @endif
</x-layouts.app>