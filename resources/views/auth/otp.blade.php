<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Verifikasi OTP - {{ config('app.name', 'Seklov') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="m-0 bg-white font-['Inter']">
        <div class="relative flex min-h-screen flex-col items-center overflow-hidden bg-white px-5 pt-16 pb-10 lg:px-0 lg:pt-[155px] lg:pb-0">

            {{-- Dekorasi pojok kanan atas dan kiri bawah (bentuk yang sama, diputar 180 derajat) --}}
            <img src="{{ asset('images/dekor-kanan-atas.svg') }}" alt=""
                class="absolute top-0 right-0 hidden h-[200px] w-[243px] lg:block drop-shadow-[0_4px_4px_rgba(0,0,0,0.25)]">
            <img src="{{ asset('images/dekor-kanan-atas.svg') }}" alt=""
                class="absolute bottom-0 left-0 hidden h-[200px] w-[243px] rotate-180 lg:block drop-shadow-[0_4px_4px_rgba(0,0,0,0.25)]">

            <h1 class="text-center font-['Poppins'] text-[30px] leading-normal lg:text-[60px] font-semibold text-[#00523f]">Verifikasi OTP</h1>
            <p class="mt-2 text-center text-[16px] font-semibold text-[#00523f] lg:mt-[25px] lg:text-[25px]">Masukan 6 digit Angka</p>

            <form id="otp-form" method="POST" action="{{ route($verifyRoute ?? 'otp.verify') }}" class="mt-8 w-full max-w-[922px] lg:mt-[48px]">
                @csrf
                <input type="hidden" name="code" id="code">

                <div class="flex flex-col items-center gap-1 text-center lg:flex-row lg:items-baseline lg:gap-[26px] lg:text-left">
                    <p class="text-[14px] font-medium text-black lg:text-[20px]">Kami telah mengirim Kode OTP ke email</p>
                    <p class="break-all font-['Poppins'] text-[14px] font-medium text-[#00523f] lg:text-[20px]">{{ $email }}</p>
                </div>

                <div class="mt-4 grid grid-cols-6 gap-2 lg:mt-[13px] lg:flex lg:gap-[20px]">
                    @for ($i = 1; $i <= 6; $i++)
                        <input type="text" data-otp-box inputmode="numeric" maxlength="1" autocomplete="{{ $i === 1 ? 'one-time-code' : 'off' }}"
                            aria-label="Digit ke-{{ $i }}"
                            class="h-14 w-full min-w-0 rounded-[5px] border-2 border-[#00523f] bg-white text-center text-[28px] lg:h-[170px] lg:w-[137px] lg:text-[100px] font-semibold text-black shadow-[0_4px_4px_rgba(0,0,0,0.25)]">
                    @endfor
                </div>

                <div class="mt-4 flex flex-wrap items-baseline justify-center gap-2 text-[14px] font-medium lg:mt-[20px] lg:justify-start lg:gap-[17px] lg:text-[20px]">
                    <span class="text-black">Tidak menerima kode ?</span>
                    <button type="submit" form="resend-form" class="cursor-pointer text-[#00523f]">Kirim ulang</button>
                </div>

                <div class="mt-3 min-h-[24px] text-center text-[14px] lg:mt-[16px] lg:text-left lg:text-[18px]">
                    @if (session('status'))
                        <p class="text-[#00523f]">{{ session('status') }}</p>
                    @endif
                    @error('code')
                        <p class="text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="mx-auto mt-10 block h-12 w-full max-w-[596px] cursor-pointer rounded-[10px] border border-[#00523f] bg-[#00523f] text-[18px] lg:mt-[195px] lg:h-[72px] lg:text-[40px] font-semibold text-white">
                    Verifikasi
                </button>
            </form>

            {{-- Form terpisah untuk kirim ulang (dipanggil lewat atribut form pada tombol "Kirim ulang") --}}
            <form id="resend-form" method="POST" action="{{ route($resendRoute ?? 'otp.resend') }}">
                @csrf
            </form>
        </div>

        <script>
            (function () {
                const boxes = Array.from(document.querySelectorAll('[data-otp-box]'));
                const hidden = document.getElementById('code');
                const form = document.getElementById('otp-form');

                boxes.forEach(function (box, i) {
                    // Hanya angka; pindah ke kotak berikutnya setelah diisi.
                    box.addEventListener('input', function () {
                        box.value = box.value.replace(/\D/g, '').slice(0, 1);
                        if (box.value && boxes[i + 1]) {
                            boxes[i + 1].focus();
                        }
                    });

                    // Backspace pada kotak kosong kembali ke kotak sebelumnya.
                    box.addEventListener('keydown', function (e) {
                        if (e.key === 'Backspace' && !box.value && boxes[i - 1]) {
                            boxes[i - 1].focus();
                        }
                    });

                    // Tempel 6 digit sekaligus.
                    box.addEventListener('paste', function (e) {
                        const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, boxes.length);
                        if (!digits) {
                            return;
                        }
                        e.preventDefault();
                        digits.split('').forEach(function (d, k) {
                            boxes[k].value = d;
                        });
                        boxes[Math.min(digits.length, boxes.length - 1)].focus();
                    });
                });

                // Gabungkan 6 kotak menjadi satu nilai "code" saat dikirim.
                form.addEventListener('submit', function () {
                    hidden.value = boxes.map(function (b) { return b.value; }).join('');
                });

                boxes[0].focus();
            })();
        </script>
    </body>
</html>