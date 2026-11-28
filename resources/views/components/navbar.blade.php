@props(['detail' => false, 'search' => null, 'filters' => []])

<nav class="mx-auto hidden w-full flex-wrap items-center justify-between gap-3 rounded-[18px] bg-[#00523f] text-white shadow-[0_8px_24px_rgba(0,82,63,0.12)] lg:flex {{ $detail ? 'max-w-[1184px] px-4 py-2 sm:px-5' : 'max-w-[1320px] px-5 py-3 sm:px-7' }}">
    <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3" aria-label="Seklov, beranda">
        <span class="flex {{ $detail ? 'size-12' : 'size-10' }} items-center justify-center rounded-[12px] bg-white font-['Poppins'] text-[22px] font-bold text-[#00523f]">S</span>
        <span class="font-['Poppins'] text-[21px] font-semibold tracking-[-0.04em]">Seklov</span>
    </a>

    @if ($search !== null)
        <form method="GET" action="{{ route('home') }}" role="search" class="order-last w-full md:order-none md:max-w-[460px] md:flex-1">
            @foreach ($filters as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <label class="flex min-h-10 items-center gap-2 rounded-[12px] bg-white px-3.5 text-[#202b3d]">
                <svg class="size-[18px] shrink-0 text-[#718096]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.8"/>
                    <path d="m16 16 4.2 4.2" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/>
                </svg>
                <span class="sr-only">Cari nama barang</span>
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama barang..." class="min-w-0 flex-1 border-0 bg-transparent text-[14px] outline-none placeholder:text-[#98a2ad] focus:ring-0">
            </label>
        </form>
    @endif

    <div class="flex items-center gap-2 sm:gap-3">
        @auth
            <div class="flex items-center gap-1 sm:gap-2">
                <a href="{{ route('cart.index') }}" title="Keranjang" aria-label="Keranjang" class="relative flex size-10 items-center justify-center rounded-[12px] bg-white text-[#00523f] transition hover:bg-[#edf7f4] sm:size-11">
                    <svg class="size-5 sm:size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 4h2l2.2 11.1a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 1.9-1.4L21 8H6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
                        <circle cx="10" cy="20" r="1.2" fill="currentColor"/>
                        <circle cx="18" cy="20" r="1.2" fill="currentColor"/>
                    </svg>
                    @if (($cartCount ?? 0) > 0)
                        <span data-cart-badge class="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full bg-[#e53e3e] text-[10px] font-bold text-white shadow">{{ $cartCount > 9 ? '9+' : $cartCount }}</span>
                    @endif
                </a>
                <a href="{{ route('transactions.index') }}" title="Daftar Transaksi" aria-label="Daftar Transaksi" class="flex size-10 items-center justify-center rounded-[12px] bg-white text-[#00523f] transition hover:bg-[#edf7f4] sm:size-11">
                    <svg class="size-5 sm:size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 3.5h9l4 4V20a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M14.5 3.8V8h4.2M9 12h6M9 16h6" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/>
                    </svg>
                </a>
                <a href="{{ route('wishlist.index') }}" title="Wishlist / Permintaan Barang" aria-label="Wishlist / Permintaan Barang" class="flex size-10 items-center justify-center rounded-[12px] bg-white text-[#00523f] transition hover:bg-[#edf7f4] sm:size-11">
                    <svg class="size-5 sm:size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 20.5s-8-4.6-8-11A4.5 4.5 0 0 1 12 6.8 4.5 4.5 0 0 1 20 9.5c0 6.4-8 11-8 11Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
                    </svg>
                </a>
                <a href="{{ route('chat.index') }}" title="Pesan" aria-label="Pesan" class="flex size-10 items-center justify-center rounded-[12px] bg-white text-[#00523f] transition hover:bg-[#edf7f4] sm:size-11">
                    <svg class="size-5 sm:size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
                    </svg>
                </a>
                <button type="button" data-notification-trigger title="Pusat Notifikasi" aria-label="Pusat Notifikasi" class="relative flex size-10 items-center justify-center rounded-[12px] bg-white text-[#00523f] transition hover:bg-[#edf7f4] sm:size-11">
                    <svg class="size-5 sm:size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
                    </svg>
                    <span data-notification-badge class="{{ ($unreadNotificationCount ?? 0) > 0 ? '' : 'hidden ' }}absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full bg-[#e53e3e] text-[10px] font-bold text-white shadow">
                        {{ ($unreadNotificationCount ?? 0) > 9 ? '9+' : ($unreadNotificationCount ?? 0) }}
                    </span>
                </button>
            </div>
        @endauth

        @guest
            <a href="{{ route('login') }}" class="rounded-[10px] px-3 py-2 text-[14px] font-semibold text-white hover:bg-white/10 sm:px-4">Login</a>
            <a href="{{ route('register') }}" class="rounded-[10px] bg-white px-4 py-2 text-[14px] font-semibold text-[#00523f] hover:bg-[#ececec]">Register</a>
        @else
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="rounded-[10px] bg-white/10 px-3 py-2 text-[13px] font-semibold hover:bg-white/20 sm:px-4">Dashboard Admin</a>
            @endif
            <span class="hidden max-w-[140px] truncate text-[13px] font-semibold text-white sm:block">{{ auth()->user()->name }}</span>
            <a href="{{ route('profile.me') }}" title="Profil saya" aria-label="Profil saya" class="shrink-0 rounded-full ring-2 ring-white/70 transition hover:ring-white">
                <x-avatar :user="auth()->user()" size="size-10 sm:size-11" text="text-base" />
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-[10px] border border-white/40 px-3 py-2 text-[13px] font-semibold hover:bg-white/10 sm:px-4">Logout</button>
            </form>
        @endguest
    </div>
</nav>

<div class="mx-auto flex w-full max-w-[1320px] items-center justify-between rounded-[18px] bg-[#00523f] px-4 py-3 text-white shadow-[0_8px_24px_rgba(0,82,63,0.12)] lg:hidden">
    <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3" aria-label="Seklov, beranda">
        <span class="flex size-10 items-center justify-center rounded-[12px] bg-white font-['Poppins'] text-[22px] font-bold text-[#00523f]">S</span>
        <span class="font-['Poppins'] text-[21px] font-semibold tracking-[-0.04em]">Seklov</span>
    </a>
    <button
        type="button"
        data-mobile-navigation-toggle
        aria-label="Buka menu navigasi"
        aria-controls="mobile-navigation"
        aria-expanded="false"
        class="flex size-10 items-center justify-center rounded-[10px] transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
    >
        <svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-linecap="round" stroke-width="2"/>
        </svg>
    </button>
</div>

<div data-mobile-navigation class="pointer-events-none fixed inset-0 z-[60] lg:hidden" aria-hidden="true">
    <button type="button" data-mobile-navigation-overlay tabindex="-1" aria-label="Tutup menu navigasi" class="absolute inset-0 h-full w-full bg-black/50 opacity-0 transition-opacity duration-300 ease-out"></button>
    <aside id="mobile-navigation" data-mobile-navigation-drawer role="dialog" aria-modal="true" aria-label="Menu navigasi Seklov" class="absolute inset-y-0 left-0 flex w-[min(20rem,85vw)] -translate-x-full flex-col overflow-y-auto bg-white text-[#202b3d] shadow-2xl transition-transform duration-300 ease-out">
        <div class="flex items-center justify-between bg-[#00523f] px-5 py-4 text-white">
            <a href="{{ route('home') }}" data-mobile-navigation-close class="flex items-center gap-3" aria-label="Seklov, beranda">
                <span class="flex size-10 items-center justify-center rounded-[12px] bg-white font-['Poppins'] text-[22px] font-bold text-[#00523f]">S</span>
                <span class="font-['Poppins'] text-[21px] font-semibold tracking-[-0.04em]">Seklov</span>
            </a>
            <button type="button" data-mobile-navigation-close aria-label="Tutup menu navigasi" class="flex size-10 items-center justify-center rounded-[10px] transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-linecap="round" stroke-width="2"/>
                </svg>
            </button>
        </div>

        <div class="space-y-5 p-5">
            <form method="GET" action="{{ route('home') }}" role="search">
                @foreach ($filters as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <label class="flex min-h-11 items-center gap-2 rounded-[12px] border border-[#e1e6e9] bg-[#f7f9fa] px-3.5 text-[#202b3d]">
                    <svg class="size-[18px] shrink-0 text-[#718096]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.8"/>
                        <path d="m16 16 4.2 4.2" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/>
                    </svg>
                    <span class="sr-only">Cari nama barang</span>
                    <input type="search" name="q" value="{{ $search ?? request('q', '') }}" placeholder="Cari nama barang..." class="min-w-0 flex-1 border-0 bg-transparent text-[14px] outline-none placeholder:text-[#98a2ad] focus:ring-0">
                </label>
            </form>

            <x-sell-button data-mobile-navigation-close class="flex min-h-11 w-full items-center justify-center rounded-[10px] bg-[#00523f] px-4 text-[14px] font-semibold text-white transition hover:bg-[#006c52]" />

            @auth
                <div class="space-y-1 border-t border-[#edf0ee] pt-3">
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-[#718096]">Menu</p>
                    <a href="{{ route('cart.index') }}" data-mobile-navigation-close class="flex min-h-11 items-center justify-between rounded-[10px] px-3 text-[14px] font-medium transition hover:bg-[#edf7f4]">
                        <span>Keranjang</span>
                        @if (($cartCount ?? 0) > 0)
                            <span class="flex size-5 items-center justify-center rounded-full bg-[#e53e3e] text-[10px] font-bold text-white">{{ $cartCount > 9 ? '9+' : $cartCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('transactions.index') }}" data-mobile-navigation-close class="flex min-h-11 items-center rounded-[10px] px-3 text-[14px] font-medium transition hover:bg-[#edf7f4]">Daftar Transaksi</a>
                    <a href="{{ route('wishlist.index') }}" data-mobile-navigation-close class="flex min-h-11 items-center rounded-[10px] px-3 text-[14px] font-medium transition hover:bg-[#edf7f4]">Wishlist / Permintaan Barang</a>
                    <a href="{{ route('chat.index') }}" data-mobile-navigation-close class="flex min-h-11 items-center rounded-[10px] px-3 text-[14px] font-medium transition hover:bg-[#edf7f4]">Pesan</a>
                    <button type="button" data-notification-trigger data-mobile-navigation-close aria-label="Buka Pusat Notifikasi" class="flex min-h-11 w-full items-center justify-between rounded-[10px] px-3 text-left text-[14px] font-medium transition hover:bg-[#edf7f4]">
                        <span>Pusat Notifikasi</span>
                        <span data-notification-badge class="{{ ($unreadNotificationCount ?? 0) > 0 ? '' : 'hidden ' }}flex size-5 items-center justify-center rounded-full bg-[#e53e3e] text-[10px] font-bold text-white">
                            {{ ($unreadNotificationCount ?? 0) > 9 ? '9+' : ($unreadNotificationCount ?? 0) }}
                        </span>
                    </button>
                    <a href="{{ route('notifications.index') }}" data-mobile-navigation-close class="flex min-h-11 items-center rounded-[10px] px-3 text-[14px] font-medium transition hover:bg-[#edf7f4]">Halaman Notifikasi</a>
                </div>

                <div class="space-y-1 border-t border-[#edf0ee] pt-3">
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" data-mobile-navigation-close class="flex min-h-11 items-center rounded-[10px] px-3 text-[14px] font-medium transition hover:bg-[#edf7f4]">Dashboard Admin</a>
                    @endif
                    <a href="{{ route('profile.me') }}" data-mobile-navigation-close class="flex min-h-11 items-center gap-3 rounded-[10px] px-3 text-[14px] font-medium transition hover:bg-[#edf7f4]">
                        <x-avatar :user="auth()->user()" size="size-9" text="text-sm" />
                        <span class="min-w-0 flex-1 truncate">{{ auth()->user()->name }} · Profil saya</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex min-h-11 w-full items-center rounded-[10px] px-3 text-left text-[14px] font-medium text-[#a12b2b] transition hover:bg-red-50">Logout</button>
                    </form>
                </div>
            @else
                <div class="space-y-1 border-t border-[#edf0ee] pt-3">
                    <a href="{{ route('login') }}" data-mobile-navigation-close class="flex min-h-11 items-center rounded-[10px] px-3 text-[14px] font-semibold text-[#00523f] transition hover:bg-[#edf7f4]">Login</a>
                    <a href="{{ route('register') }}" data-mobile-navigation-close class="flex min-h-11 items-center rounded-[10px] px-3 text-[14px] font-semibold text-[#00523f] transition hover:bg-[#edf7f4]">Register</a>
                </div>
            @endauth
        </div>
    </aside>
</div>

@auth
    @include('notifications._modal')
@endauth
