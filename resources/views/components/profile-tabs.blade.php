@props(['user', 'tab', 'isOwner' => false, 'counts' => []])

@php
    $url = fn (string $key) => $isOwner
        ? route('profile.me', ['tab' => $key])
        : route('store.show', ['user' => $user, 'tab' => $key]);

    $tabs = [
        'shop' => 'Shop ('.($counts['shop'] ?? 0).')',
        'wishlist' => 'Wishlist / Permintaan Barang ('.($counts['wishlist'] ?? 0).')',
        'reviews' => 'Reviews / Ulasan ('.($counts['reviews'] ?? 0).')',
    ];
@endphp

<nav class="mt-6 flex gap-4 overflow-x-auto border-b border-[#e1e6e9] sm:mt-8 sm:gap-8" aria-label="Tab profil">
    @foreach ($tabs as $key => $label)
        <a href="{{ $url($key) }}" @if ($tab === $key) aria-current="page" @endif
           class="-mb-px flex min-h-11 shrink-0 items-center whitespace-nowrap border-b-2 py-3 text-[14px] sm:text-[16px] {{ $tab === $key ? 'border-[#00523f] font-medium text-[#00523f]' : 'border-transparent text-[#718096] hover:text-[#00523f]' }}">
            {{ $label }}
        </a>
    @endforeach
</nav>