@props(['user', 'isOwner' => false, 'ratingAvg' => 0, 'ratingTotal' => 0])

@php
    $campusNames = [
        'UNSIKA' => 'Universitas Singaperbangsa Karawang',
        'UBP' => 'Universitas Buana Perjuangan Karawang',
    ];
@endphp

<section class="flex flex-col gap-5 rounded-[18px] border border-[#e7ebee] bg-white p-5 shadow-[0_3px_12px_rgba(22,34,48,0.04)] sm:flex-row sm:items-center sm:justify-between sm:p-8">
    <div class="flex items-center gap-5 sm:gap-6">
        <x-avatar :user="$user" size="size-20 sm:size-[120px]" text="text-3xl sm:text-5xl" />

        <div class="min-w-0">
            <h1 class="truncate font-['Poppins'] text-[22px] font-semibold text-[#202b3d] sm:text-[26px]">{{ $user->name }}</h1>
            <p class="mt-1 text-[14px] text-[#718096]">{{ $campusNames[$user->campus] ?? $user->campus }}</p>
            <div class="mt-2 flex items-center gap-2.5">
                <x-star-rating :rating="$ratingAvg" />
                <span class="text-[13px] font-medium text-[#202b3d]">
                    {{ $ratingTotal > 0 ? number_format($ratingAvg, 1) : '0' }} ({{ $ratingTotal }} Ulasan)
                </span>
            </div>
        </div>
    </div>

    @if ($isOwner)
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('profile.edit') }}" class="inline-flex h-11 items-center justify-center rounded-[10px] border border-[#00523f] px-5 text-[14px] font-semibold text-[#00523f] transition hover:bg-[#edf7f4]">Edit Profil</a>
            <a href="{{ route('products.create') }}" class="inline-flex h-11 items-center justify-center rounded-[10px] bg-[#00523f] px-6 text-[14px] font-semibold text-white transition hover:bg-[#006c52]">+ Jual Barang</a>
        </div>
    @else
        <button type="button" disabled title="Chat dari profil segera hadir" class="inline-flex h-11 cursor-not-allowed items-center justify-center gap-2 rounded-[10px] bg-[#00523f] px-6 text-[14px] font-semibold text-white opacity-60">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
            </svg>
            Chat Sekarang
        </button>
    @endif
</section>