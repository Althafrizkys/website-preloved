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
        <div class="relative flex min-h-screen flex-col items-center overflow-hidden bg-white pt-[155px]">

            {{-- Dekorasi pojok kanan atas dan kiri bawah (bentuk yang sama, diputar 180 derajat) --}}
            <img src="{{ asset('images/dekor-kanan-atas.svg') }}" alt=""
                class="absolute top-0 right-0 h-[200px] w-[243px] drop-shadow-[0_4px_4px_rgba(0,0,0,0.25)]">
            <img src="{{ asset('images/dekor-kanan-atas.svg') }}" alt=""
                class="absolute bottom-0 left-0 h-[200px] w-[243px] rotate-180 drop-shadow-[0_4px_4px_rgba(0,0,0,0.25)]">

            <h1 class="font-['Poppins'] text-[60px] leading-normal font-semibold text-[#00523f]">Verifikasi OTP</h1>
            <p class="mt-[25px] text-[25px] font-semibold text-[#00523f]">Masukan 6 digit Angka</p>

            <form id="otp-form" method="POST" action="{{ route('otp.verify') }}" class="mt-[48px] w-[922px]">
                @csrf
                <input type="hidden" name="code" id="code">

                <div class="flex items-baseline gap-[26px]">
                    <p class="text-[20px] font-medium text-black">Kami telah mengirim Kode OTP ke email</p>
                    <p class="font-['Poppins'] text-[20px] font-medium text-[#00523f]">{{ $email }}</p>
                </div>

                <div class="mt-[13px] flex gap-[20px]">
                    @for ($i = 1; $i <= 6; $i++)
                        <input type="text" data-otp-box inputmode="numeric" maxlength="1" autocomplete="{{ $i === 1 ? 'one-time-code' : 'off' }}"
                            aria-label="Digit ke-{{ $i }}"
                            class="h-[170px] w-[137px] rounded-[5px] border-2 border-[#00523f] bg-white text-center text-[100px] font-semibold text-black shadow-[0_4px_4px_rgba(0,0,0,0.25)]">
                    @endfor
                </div>

                <div class="mt-[20px] flex items-baseline gap-[17px] text-[20px] font-medium">
                    <span class="text-black">Tidak menerima kode ?</span>
                    <button type="submit" form="resend-form" class="cursor-pointer text-[#00523f]">Kirim ulang</button>
                </div>

                <div class="mt-[16px] min-h-[24px] text-[18px]">
                    @if (session('status'))
                        <p class="text-[#00523f]">{{ session('status') }}</p>
                    @endif
                    @error('code')
                        <p class="text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="mx-auto mt-[195px] block h-[72px] w-[596px] cursor-pointer rounded-[10px] border border-[#00523f] bg-[#00523f] text-[40px] font-semibold text-white">
                    Verifikasi
                </button>
            </form>

            {{-- Form terpisah untuk kirim ulang (dipanggil lewat atribut form pada tombol "Kirim ulang") --}}
            <form id="resend-form" method="POST" action="{{ route('otp.resend') }}">
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