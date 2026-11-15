<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Password Baru - {{ config('app.name', 'Seklov') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@600&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="m-0 bg-white font-['Inter']">
        <div class="relative flex min-h-screen overflow-hidden bg-white">

            {{-- Dekorasi pojok kanan atas --}}
            <div class="absolute top-0 right-0 h-[200px] w-[243px]">
                <div class="absolute inset-[0_-1.65%_-4%_-1.65%]">
                    <img src="{{ asset('images/dekor-kanan-atas.svg') }}" alt="" class="block size-full max-w-none">
                </div>
            </div>

            {{-- Panel kiri --}}
            <div class="flex w-[55%] flex-col items-center justify-center rounded-tr-[383px] rounded-bl-[396px] bg-linear-to-b from-[#00523c] via-[#005a42] via-[44.231%] to-[#00b888] shadow-[0_4px_4px_rgba(0,0,0,0.25)]">
                {{-- Logo: sementara placeholder lingkaran putih --}}
                <div class="size-[182px] rounded-full bg-white shadow-[0_4px_4px_rgba(0,0,0,0.25)]"></div>

                <h1 class="mt-[29px] text-center font-['Poppins'] text-[48px] leading-[72px] font-semibold text-[#ececec] [text-shadow:0_4px_4px_rgba(0,0,0,0.25)]">
                    WELCOME TO<br>SEKLOV
                </h1>

                <div class="mt-[184px] w-[395px]">
                    <p class="text-center text-[24px] text-white [text-shadow:0_4px_4px_rgba(0,0,0,0.25)]">Belum punya akun?</p>
                    <a href="{{ route('register') }}" class="mt-[12px] flex h-[48px] w-full items-center justify-center rounded-[10px] bg-white text-[24px] font-semibold text-[#00523f]">
                        Register
                    </a>
                </div>
            </div>

            {{-- Panel kanan --}}
            <div class="flex flex-1 items-center justify-center">
                <div class="w-[395px]">
                    <h2 class="text-center font-['Poppins'] text-[48px] leading-normal font-semibold text-[#00523f] [text-shadow:0_4px_4px_rgba(0,0,0,0.25)]">
                        Password Baru
                    </h2>
                    <p class="mt-[8px] min-h-[48px] text-center text-[20px] leading-[24px] text-[#00523f]">
                        Buat password baru untuk akunmu
                    </p>

                    <form method="POST" action="{{ route('password.update') }}" class="mt-[30px]">
                        @csrf

                        <div>
                            <label for="password" class="block text-[24px] text-[#00523f]">Password Baru :</label>
                            <input id="password" name="password" type="password" autocomplete="new-password"
                                class="mt-[6px] block h-[48px] w-full rounded-[5px] bg-[rgba(0,82,63,0.6)] px-[21px] text-[24px] text-white">
                            @error('password')
                                <p class="mt-1 text-[16px] text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-[11px]">
                            <label for="password_confirmation" class="block text-[24px] text-[#00523f]">Konfirmasi Password :</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                                class="mt-[6px] block h-[48px] w-full rounded-[5px] bg-[rgba(0,82,63,0.6)] px-[21px] text-[24px] text-white">
                        </div>

                        <button type="submit"
                            class="mt-[33px] h-[48px] w-full cursor-pointer rounded-[10px] border border-[#00523f] bg-[#00523f] text-[24px] font-semibold text-white">
                            Simpan Password
                        </button>

                        <a href="{{ route('login') }}" class="mt-[12px] block text-center text-[24px] text-[#00523f] [text-shadow:0_4px_4px_rgba(0,0,0,0.25)]">
                            Kembali ke Login
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>