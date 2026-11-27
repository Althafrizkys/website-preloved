<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Login Admin - {{ config('app.name', 'Seklov') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@600&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="m-0 bg-white font-['Inter']">
        <div class="relative flex min-h-screen flex-col overflow-hidden bg-white lg:flex-row">

            {{-- Dekorasi pojok kanan atas --}}
            <div class="absolute top-0 right-0 hidden h-[200px] w-[243px] lg:block">
                <div class="absolute inset-[0_-1.65%_-4%_-1.65%]">
                    <img src="{{ asset('images/dekor-kanan-atas.svg') }}" alt="" class="block size-full max-w-none">
                </div>
            </div>

            {{-- Panel kiri --}}
            <div class="flex w-full flex-col items-center justify-center rounded-b-[48px] px-6 py-10 shadow-[0_4px_4px_rgba(0,0,0,0.25)] bg-linear-to-b from-[#00523c] via-[#005a42] via-[44.231%] to-[#00b888] lg:w-[55%] lg:rounded-b-none lg:rounded-tr-[383px] lg:rounded-bl-[396px] lg:p-0">
                {{-- Logo: sementara placeholder lingkaran putih --}}
                <div class="size-[88px] rounded-full bg-white shadow-[0_4px_4px_rgba(0,0,0,0.25)] lg:size-[182px]"></div>

                <h1 class="mt-4 text-center font-['Poppins'] text-[28px] leading-[40px] font-semibold text-[#ececec] [text-shadow:0_4px_4px_rgba(0,0,0,0.25)] lg:mt-[29px] lg:text-[48px] lg:leading-[72px]">
                    WELCOME TO<br>SEKLOV
                </h1>
            </div>

            {{-- Panel kanan --}}
            <div class="flex w-full flex-1 items-start justify-center px-6 py-8 lg:items-center lg:p-0">
                <div class="w-full max-w-[395px]">
                    <h2 class="mb-8 text-center font-['Poppins'] text-[32px] leading-normal font-semibold text-[#00523f] [text-shadow:0_4px_4px_rgba(0,0,0,0.25)] lg:mb-[70px] lg:text-[48px]">
                        Login Admin
                    </h2>

                    @if (session('status'))
                        <p class="mb-4 text-center text-[16px] text-[#00523f]">{{ session('status') }}</p>
                    @endif

                    <form method="POST" action="{{ route('admin.login') }}">
                        @csrf

                        <div>
                            <label for="email" class="block text-[16px] text-[#00523f] lg:text-[24px]">email :</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="email" autocomplete="email"
                                class="mt-[6px] block h-[48px] w-full rounded-[5px] bg-[rgba(0,82,63,0.6)] px-4 text-[16px] text-white placeholder:text-[#ececec] lg:px-[21px] lg:text-[24px]">
                            @error('email')
                                <p class="mt-1 text-[16px] text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-[11px]">
                            <label for="password" class="block text-[16px] text-[#00523f] lg:text-[24px]">Password :</label>
                            <input id="password" name="password" type="password" placeholder="password" autocomplete="current-password"
                                class="mt-[6px] block h-[48px] w-full rounded-[5px] bg-[rgba(0,82,63,0.6)] px-4 text-[16px] text-white placeholder:text-[#ececec] lg:px-[21px] lg:text-[24px]">
                            @error('password')
                                <p class="mt-1 text-[16px] text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                            class="mt-[33px] h-12 w-full cursor-pointer rounded-[10px] border border-[#00523f] bg-[#00523f] text-[18px] font-semibold text-white lg:text-[24px]">
                            Login
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>