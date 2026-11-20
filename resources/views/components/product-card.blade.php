@props(['product'])

<a href="{{ route('products.show', $product) }}" class="group flex h-full flex-col overflow-hidden rounded-[14px] border border-[#e5e9ed] bg-white shadow-[0_3px_10px_rgba(22,34,48,0.06)] transition hover:-translate-y-0.5 hover:shadow-[0_10px_24px_rgba(22,34,48,0.10)]">
    <div class="relative aspect-[4/3] overflow-hidden bg-[#eceff2]">
        @if ($product->media->isNotEmpty())
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->media->first()->media_url) }}" alt="{{ $product->product_name }}" class="size-full object-cover transition duration-300 group-hover:scale-[1.03]">
        @else
            <div class="flex size-full items-center justify-center bg-gradient-to-br from-[#eef1f3] to-[#e2e7e9] text-[#98a4a8]" aria-label="Foto belum tersedia">
                <svg class="size-12" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                    <path d="M8 11.5A3.5 3.5 0 0 1 11.5 8h25a3.5 3.5 0 0 1 3.5 3.5v25a3.5 3.5 0 0 1-3.5 3.5h-25A3.5 3.5 0 0 1 8 36.5v-25Z" stroke="currentColor" stroke-width="2.5"/>
                    <circle cx="18" cy="18" r="3" stroke="currentColor" stroke-width="2.5"/>
                    <path d="m10 33 9-9 6 6 4-4 9 9" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"/>
                </svg>
            </div>
        @endif
        <span class="absolute left-3 top-3 rounded-full bg-[#00523f] px-3 py-1 text-[11px] font-semibold text-white">Tersedia</span>
    </div>

    <div class="flex flex-1 flex-col p-3.5 sm:p-4">
        <p class="line-clamp-2 min-h-[42px] text-[14px] font-semibold leading-[21px] text-[#202b3d]">{{ $product->product_name }}</p>
        <div class="mt-1.5">
            <span data-product-category="{{ $product->category?->category_name ?? 'Lainnya' }}" class="rounded-full bg-[#f0f3f6] px-2.5 py-1 text-[10px] font-medium text-[#526170]">{{ $product->category?->category_name ?? 'Lainnya' }}</span>
        </div>
        <p class="mt-2 text-[17px] font-bold text-[#00523f]">Rp {{ number_format((float) $product->price, 0, ',', '.') }}</p>
        <div class="mt-auto flex flex-wrap items-center justify-between gap-x-2 gap-y-1 pt-3 text-[12px] text-[#718096]">
            <span>{{ $product->condition }}</span>
            <span>{{ $product->seller?->campus }}</span>
        </div>
    </div>
</a>
