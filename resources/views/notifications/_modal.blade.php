<dialog
    data-notification-dialog
    aria-label="Pusat Notifikasi"
    class="fixed inset-0 m-auto max-h-[92vh] w-[94vw] max-w-[480px] overflow-hidden rounded-[24px] bg-white p-0 text-[#202b3d] shadow-[0_25px_70px_rgba(25,40,52,0.25)] backdrop:bg-[#202b3d]/45 border-0"
>
    <div class="max-h-[92vh] overflow-y-auto overscroll-contain p-5 sm:p-6">
        {{-- Header: Judul & Tombol Tutup X --}}
        <div class="flex items-center justify-between">
            <h2 class="font-['Poppins'] text-[20px] font-semibold tracking-[-0.02em] text-[#00523f]">Pusat Notifikasi</h2>
            <button
                type="button"
                data-notification-close
                aria-label="Tutup notifikasi"
                class="flex size-11 shrink-0 items-center justify-center rounded-full text-[#00523f] transition hover:bg-[#edf7f4]"
            >
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Filter Tabs (Semua / Belum dibaca) --}}
        <div class="mt-4 flex rounded-full bg-[#f0f2f1] p-1 text-[14px] font-medium text-[#5c6f68]">
            <button
                type="button"
                data-notification-tab="semua"
                class="min-h-11 flex-1 rounded-full py-1.5 text-center font-semibold bg-white text-[#00523f] shadow-sm transition"
            >
                Semua
            </button>
            <button
                type="button"
                data-notification-tab="belum_dibaca"
                class="min-h-11 flex-1 rounded-full py-1.5 text-center font-medium text-[#5c6f68] hover:text-[#00523f] transition"
            >
                Belum dibaca
            </button>
        </div>

        {{-- Filter Role (Radio Penjual & Pembeli) --}}
        <div class="mt-3.5 flex flex-wrap items-center gap-3 text-[14px] sm:gap-5">
            <label class="flex min-h-11 cursor-pointer items-center gap-2 select-none">
                <input
                    type="radio"
                    name="modal_notification_role"
                    value="penjual"
                    checked
                    data-notification-role-radio
                    class="sr-only peer"
                >
                <span class="flex size-4 items-center justify-center rounded-full border-2 border-[#00523f] peer-checked:border-[#00523f] transition">
                    <span data-radio-dot="penjual" class="size-2 rounded-full bg-[#00523f] transition"></span>
                </span>
                <span data-radio-label="penjual" class="font-semibold text-[#00523f] transition">Penjual</span>
            </label>

            <label class="flex min-h-11 cursor-pointer items-center gap-2 select-none">
                <input
                    type="radio"
                    name="modal_notification_role"
                    value="pembeli"
                    data-notification-role-radio
                    class="sr-only peer"
                >
                <span class="flex size-4 items-center justify-center rounded-full border-2 border-[#cbd5e1] peer-checked:border-[#00523f] transition">
                    <span data-radio-dot="pembeli" class="size-2 rounded-full bg-transparent transition"></span>
                </span>
                <span data-radio-label="pembeli" class="text-[#526170] transition">Pembeli</span>
            </label>
        </div>

        {{-- Daftar Notifikasi --}}
        <div data-notification-list class="mt-4 max-h-[50vh] space-y-2.5 overflow-y-auto pr-0.5">
            @forelse ($notifications as $notification)
                @include('notifications._item', ['notification' => $notification])
            @empty
                <div data-notification-empty-initial class="rounded-[12px] bg-[#f8faf9] py-10 text-center text-[13px] text-[#718096]">
                    Belum ada notifikasi saat ini.
                </div>
            @endforelse

            <div data-notification-empty class="hidden rounded-[12px] bg-[#f8faf9] py-10 text-center text-[13px] text-[#718096]">
                Tidak ada notifikasi yang sesuai.
            </div>
        </div>

        {{-- Opsi Tandai Semua Dibaca --}}
        <div class="mt-3 flex items-center justify-between border-t border-[#edf0ee] pt-3 text-[11px] text-[#718096]">
            <a href="{{ route('notifications.index') }}" class="inline-flex min-h-11 items-center font-medium text-[#00523f] hover:underline">
                Buka Halaman Penuh →
            </a>
            <button
                type="button"
                data-mark-all-read
                class="min-h-11 font-medium text-[#00523f] hover:underline"
            >
                Tandai semua dibaca
            </button>
        </div>
    </div>
</dialog>
