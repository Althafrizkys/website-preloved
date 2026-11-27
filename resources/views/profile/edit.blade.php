<x-layouts.app title="Edit Profil">
    <nav class="mb-5 flex items-center gap-2 text-[12px] text-[#718096]" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-[#00523f]">Beranda</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('profile.me') }}" class="hover:text-[#00523f]">Profil Saya</a>
        <span aria-hidden="true">/</span>
        <span class="font-semibold text-[#202b3d]">Edit Profil</span>
    </nav>

    <section class="mx-auto max-w-[760px] rounded-[18px] border border-[#e7ebee] bg-white p-5 shadow-[0_3px_12px_rgba(22,34,48,0.04)] sm:p-8">
        <div class="mb-6">
            <h1 class="font-['Poppins'] text-[22px] font-semibold text-[#202b3d]">Edit Profil</h1>
            <p class="mt-1 text-[13px] text-[#718096]">Perbarui informasi yang ingin ditampilkan kepada pengguna lain.</p>
        </div>

        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-[10px] border border-[#f1caca] bg-[#fff5f5] p-4 text-[13px] text-[#9b3434]">
                Periksa kembali isian yang ditandai.
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-6 flex items-center gap-4">
                <x-avatar :user="$user" size="size-20" text="text-3xl" />
                <div class="min-w-0 flex-1">
                    <label for="profile_photo" class="text-[13px] font-semibold text-[#263746]">Foto profil</label>
                    <input id="profile_photo" name="profile_photo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="mt-2 block w-full text-[12px] text-[#526170] file:mr-3 file:rounded-[8px] file:border-0 file:bg-[#edf7f4] file:px-4 file:py-2 file:font-semibold file:text-[#00523f]">
                    <p class="mt-1 text-[11px] text-[#718096]">JPG, PNG, atau WEBP · Maks. 2 MB.</p>
                    @error('profile_photo') <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label for="name" class="text-[13px] font-semibold text-[#263746]">Username</label>
                    <input id="name" name="name" type="text" maxlength="255" value="{{ old('name', $user->name) }}" required class="mt-2 block min-h-11 w-full rounded-[8px] border border-[#dce3e6] px-3.5 text-[14px] outline-none focus:border-[#00523f] focus:ring-2 focus:ring-[#00523f]/10">
                    @error('name') <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="text-[13px] font-semibold text-[#263746]">Email kampus</label>
                    <input id="email" type="email" value="{{ $user->email }}" readonly class="mt-2 block min-h-11 w-full rounded-[8px] border border-[#e7ebee] bg-[#f7f9fa] px-3.5 text-[14px] text-[#718096]">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="campus" class="text-[13px] font-semibold text-[#263746]">Kampus</label>
                        <input id="campus" type="text" value="{{ $user->campus }}" readonly class="mt-2 block min-h-11 w-full rounded-[8px] border border-[#e7ebee] bg-[#f7f9fa] px-3.5 text-[14px] text-[#718096]">
                    </div>
                    <div>
                        <label for="npm" class="text-[13px] font-semibold text-[#263746]">NPM / NIM</label>
                        <input id="npm" type="text" value="{{ $user->npm ?: '-' }}" readonly class="mt-2 block min-h-11 w-full rounded-[8px] border border-[#e7ebee] bg-[#f7f9fa] px-3.5 text-[14px] text-[#718096]">
                    </div>
                </div>

                <div>
                    <label for="gender" class="text-[13px] font-semibold text-[#263746]">Jenis kelamin</label>
                    <select id="gender" name="gender" class="mt-2 block min-h-11 w-full rounded-[8px] border border-[#dce3e6] bg-white px-3.5 text-[14px] outline-none focus:border-[#00523f] focus:ring-2 focus:ring-[#00523f]/10">
                        <option value="">Pilih jenis kelamin</option>
                        <option value="Laki-laki" @selected(old('gender', $user->gender) === 'Laki-laki')>Laki-laki</option>
                        <option value="Perempuan" @selected(old('gender', $user->gender) === 'Perempuan')>Perempuan</option>
                    </select>
                    @error('gender') <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-7 flex flex-wrap justify-end gap-3">
                <a href="{{ route('profile.me') }}" class="inline-flex h-11 items-center justify-center rounded-[10px] border border-[#dce3e6] px-5 text-[14px] font-semibold text-[#526170] transition hover:bg-[#f7f9fa]">Batal</a>
                <button type="submit" class="inline-flex h-11 items-center justify-center rounded-[10px] bg-[#00523f] px-6 text-[14px] font-semibold text-white transition hover:bg-[#006c52]">Simpan</button>
            </div>
        </form>
    </section>
</x-layouts.app>
