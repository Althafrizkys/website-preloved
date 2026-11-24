<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="font-['Poppins'] text-[21px] font-semibold tracking-[-0.025em] sm:text-[26px]">{{ $isOwner ? 'Barang Saya' : 'Barang Dijual' }}</h2>
        <p class="mt-1 text-[13px] text-[#718096]">{{ $isOwner ? 'Daftar barang yang sedang kamu jual' : 'Daftar barang yang dijual oleh '.$user->name }}</p>
    </div>
    <p class="text-[13px] text-[#718096]">{{ $items->count() }} barang</p>
</div>

@if ($items->isNotEmpty())
    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
        @foreach ($items as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>
@else
    <div class="rounded-[16px] border border-dashed border-[#d6dee2] bg-white px-5 py-12 text-center">
        <h3 class="font-['Poppins'] text-[18px] font-semibold text-[#263746]">Belum ada barang yang dijual</h3>
        @if ($isOwner)
            <a href="{{ route('products.create') }}" class="mt-5 inline-flex rounded-[9px] bg-[#00523f] px-4 py-2.5 text-[13px] font-semibold text-white">Mulai jual</a>
        @endif
    </div>
@endif