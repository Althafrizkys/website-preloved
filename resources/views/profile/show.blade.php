<x-layouts.app :title="$user->name">
    <nav class="mb-5 flex items-center gap-2 text-[12px] text-[#718096]" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-[#00523f]">Beranda</a>
        <svg class="size-3" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
        <span class="font-semibold text-[#202b3d]">{{ $isOwner ? 'Profil Saya' : 'Profil Penjual' }}</span>
    </nav>

    @if (session('success'))
        <div role="status" class="mb-4 rounded-[12px] border border-[#bfe6d9] bg-[#edf7f4] px-4 py-3 text-[13px] font-medium text-[#00523f]">{{ session('success') }}</div>
    @endif

    <x-profile-header :user="$user" :is-owner="$isOwner" :rating-avg="$ratingAvg" :rating-total="$counts['reviews']" />
    <x-profile-tabs :user="$user" :tab="$tab" :is-owner="$isOwner" :counts="$counts" />

    <section class="mt-6 sm:mt-8">
        @include('profile.tabs.'.$tab)
    </section>
</x-layouts.app>