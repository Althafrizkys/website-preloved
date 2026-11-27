<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Pusat Notifikasi — Seklov</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f7f9fa] font-['Inter'] text-[#202b3d] antialiased">
        <header class="px-4 pt-4 sm:px-6 sm:pt-6">
            <x-navbar />
        </header>

        <main class="mx-auto max-w-[620px] px-4 pb-16 pt-6 sm:px-6 sm:pt-8">
            <nav class="mb-5 text-[13px] text-[#718096]" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-[#00523f]">Beranda</a>
                <span class="px-2">›</span>
                <span class="font-medium text-[#00523f]">Pusat Notifikasi</span>
            </nav>

            <div class="rounded-[24px] border border-[#e1e6e9] bg-white p-5 shadow-[0_12px_36px_rgba(22,34,48,0.06)] sm:p-7">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h1 class="font-['Poppins'] text-[22px] font-semibold tracking-[-0.025em] text-[#00523f] sm:text-[24px]">
                        Pusat Notifikasi
                    </h1>
                    <button
                        type="button"
                        data-mark-all-read
                        class="inline-flex min-h-11 items-center text-[14px] font-medium text-[#00523f] hover:underline"
                    >
                        Tandai semua dibaca
                    </button>
                </div>

                {{-- Filter Tabs (Semua / Belum dibaca) --}}
                <div class="mt-4 flex rounded-full bg-[#f0f2f1] p-1 text-[14px] font-medium text-[#5c6f68]">
                    <button
                        type="button"
                        data-notification-tab="semua"
                        class="min-h-11 flex-1 rounded-full py-2 text-center font-semibold bg-white text-[#00523f] shadow-sm transition"
                    >
                        Semua
                    </button>
                    <button
                        type="button"
                        data-notification-tab="belum_dibaca"
                        class="min-h-11 flex-1 rounded-full py-2 text-center font-medium text-[#5c6f68] hover:text-[#00523f] transition"
                    >
                        Belum dibaca
                    </button>
                </div>

                {{-- Filter Role (Radio Penjual & Pembeli) --}}
                <div class="mt-4 flex flex-wrap items-center gap-3 text-[14px] sm:gap-6">
                    <label class="flex min-h-11 cursor-pointer items-center gap-2 select-none">
                        <input
                            type="radio"
                            name="page_notification_role"
                            value="penjual"
                            checked
                            data-notification-role-radio
                            class="sr-only peer"
                        >
                        <span class="flex size-4 items-center justify-center rounded-full border-2 border-[#00523f] transition">
                            <span data-radio-dot="penjual" class="size-2 rounded-full bg-[#00523f] transition"></span>
                        </span>
                        <span data-radio-label="penjual" class="font-semibold text-[#00523f] transition">Penjual</span>
                    </label>

                    <label class="flex min-h-11 cursor-pointer items-center gap-2 select-none">
                        <input
                            type="radio"
                            name="page_notification_role"
                            value="pembeli"
                            data-notification-role-radio
                            class="sr-only peer"
                        >
                        <span class="flex size-4 items-center justify-center rounded-full border-2 border-[#cbd5e1] transition">
                            <span data-radio-dot="pembeli" class="size-2 rounded-full bg-transparent transition"></span>
                        </span>
                        <span data-radio-label="pembeli" class="text-[#526170] transition">Pembeli</span>
                    </label>
                </div>

                {{-- Daftar Notifikasi --}}
                <div data-notification-list class="mt-5 space-y-3">
                    @forelse ($notifications as $notification)
                        @include('notifications._item', ['notification' => $notification])
                    @empty
                        <div data-notification-empty-initial class="rounded-[14px] bg-[#f8faf9] py-14 text-center text-[13px] text-[#718096]">
                            Belum ada notifikasi saat ini.
                        </div>
                    @endforelse

                    <div data-notification-empty class="hidden rounded-[14px] bg-[#f8faf9] py-14 text-center text-[13px] text-[#718096]">
                        Tidak ada notifikasi yang sesuai.
                    </div>
                </div>
            </div>
        </main>

        <footer class="bg-[#00523f] px-5 py-8 text-white sm:px-8 sm:py-10">
            <div class="mx-auto flex max-w-[1320px] flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-['Poppins'] text-[20px] font-semibold">Seklov</p>
                    <p class="mt-1 text-[13px] text-white/70">Marketplace preloved mahasiswa Karawang.</p>
                </div>
                <p class="text-[12px] text-white/65">© {{ date('Y') }} Seklov. Untuk mahasiswa UNSIKA &amp; UBP.</p>
            </div>
        </footer>
    </body>
</html>
