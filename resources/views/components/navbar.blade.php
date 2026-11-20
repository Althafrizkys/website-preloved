@props(['detail' => false])

<nav class="mx-auto flex w-full items-center justify-between gap-3 rounded-[18px] bg-[#00523f] text-white shadow-[0_8px_24px_rgba(0,82,63,0.12)] {{ $detail ? 'max-w-[1184px] px-4 py-2 sm:px-5' : 'max-w-[1320px] px-5 py-3 sm:px-7' }}">
    <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3" aria-label="Seklov, beranda">
        <span class="flex {{ $detail ? 'size-12' : 'size-10' }} items-center justify-center rounded-[12px] bg-white font-['Poppins'] text-[22px] font-bold text-[#00523f]">S</span>
        <span class="font-['Poppins'] text-[21px] font-semibold tracking-[-0.04em]">Seklov</span>
    </a>

    <div class="flex items-center gap-2 sm:gap-3">
        @auth
            <div class="flex items-center gap-1 sm:gap-2">
                <button type="button" disabled title="Keranjang segera hadir" aria-label="Keranjang segera hadir" class="flex size-10 cursor-not-allowed items-center justify-center rounded-[12px] bg-white text-[#00523f] opacity-80 sm:size-11">
                    <svg class="size-5 sm:size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 4h2l2.2 11.1a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 1.9-1.4L21 8H6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
                        <circle cx="10" cy="20" r="1.2" fill="currentColor"/>
                        <circle cx="18" cy="20" r="1.2" fill="currentColor"/>
                    </svg>
                </button>
                <button type="button" disabled title="Daftar transaksi segera hadir" aria-label="Daftar transaksi segera hadir" class="flex size-10 cursor-not-allowed items-center justify-center rounded-[12px] bg-white text-[#00523f] opacity-80 sm:size-11">
                    <svg class="size-5 sm:size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 3.5h9l4 4V20a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M14.5 3.8V8h4.2M9 12h6M9 16h6" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/>
                    </svg>
                </button>
                <button type="button" disabled title="Notifikasi segera hadir" aria-label="Notifikasi segera hadir" class="flex size-10 cursor-not-allowed items-center justify-center rounded-[12px] bg-white text-[#00523f] opacity-80 sm:size-11">
                    <svg class="size-5 sm:size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
                    </svg>
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
            <span class="hidden max-w-[180px] truncate text-[14px] font-medium sm:inline">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-[10px] border border-white/40 px-3 py-2 text-[13px] font-semibold hover:bg-white/10 sm:px-4">Logout</button>
            </form>
        @endguest
    </div>
</nav>
