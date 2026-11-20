<nav class="mx-auto flex w-full max-w-[1320px] items-center justify-between gap-4 rounded-[18px] bg-[#00523f] px-5 py-3 text-white shadow-[0_8px_24px_rgba(0,82,63,0.12)] sm:px-7">
    <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3" aria-label="Seklov, beranda">
        <span class="flex size-10 items-center justify-center rounded-[12px] bg-white font-['Poppins'] text-[22px] font-bold text-[#00523f]">S</span>
        <span class="font-['Poppins'] text-[21px] font-semibold tracking-[-0.04em]">Seklov</span>
    </a>

    <div class="flex items-center gap-2 sm:gap-3">
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
