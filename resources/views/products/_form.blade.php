@php
    $isEditing = $product !== null;
    $formAction = $isEditing ? route('products.update', $product) : route('products.store');
    $photoMaxCount = config('seklov.products.max_photos');
    $photoMaxMb = (int) (config('seklov.products.photo_max_kb') / 1024);
    $videoMaxMb = (int) (config('seklov.products.video_max_kb') / 1024);
    $negotiableValue = (string) old('is_negotiable', $product?->is_negotiable ? '1' : '0');
@endphp

<form id="product-form" data-product-form data-max-photos="{{ $photoMaxCount }}" class="mt-5" method="POST" action="{{ $formAction }}" enctype="multipart/form-data">
    @csrf
    @if ($isEditing)
        @method('PUT')
        <input type="hidden" name="stock_original" value="{{ old('stock_original', $product->stock) }}">
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-[10px] border border-[#f1caca] bg-[#fff5f5] p-4 text-[13px] text-[#9b3434]" role="alert">
            Periksa kembali isian yang ditandai sebelum menyimpan.
        </div>
    @endif

    <div>
        <div class="mb-2 flex items-center justify-between gap-2">
            <label for="photos" class="text-[13px] font-semibold text-[#263746]">Foto produk</label>
            <span class="text-[11px] text-[#718096]">Minimal 1 foto · Maks. {{ $photoMaxCount }} foto</span>
        </div>
        <div class="rounded-[10px] border border-dashed border-[#cfd8dc] bg-[#fafbfc] p-3.5">
            @if ($isEditing)
                <div data-existing-media-list class="mb-3 flex flex-wrap gap-2.5">
                    @foreach ($product->media as $media)
                        <div data-existing-media="{{ $media->media_id }}" data-existing-media-type="{{ $media->media_type }}" data-existing-media-size="{{ $media->file_size }}" class="relative size-[76px] overflow-hidden rounded-[8px] border border-[#dce3e6] bg-white">
                            @if ($media->media_type === 'foto')
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($media->media_url) }}" alt="Foto produk" class="size-full object-cover">
                            @else
                                <video src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($media->media_url) }}" class="size-full object-cover"></video>
                                <span class="absolute bottom-1 left-1 rounded bg-black/60 px-1 text-[9px] text-white">Video</span>
                            @endif
                            <button type="button" data-remove-existing-media="{{ $media->media_id }}" aria-label="Hapus media ini" class="absolute right-1 top-1 flex size-5 items-center justify-center rounded-full bg-white/95 text-[14px] leading-none text-[#8b3030] shadow">×</button>
                        </div>
                    @endforeach
                </div>
            @endif

            <div data-new-photo-preview class="flex flex-wrap gap-2.5"></div>
            <label for="photos" class="mt-1 flex min-h-11 cursor-pointer items-center justify-center rounded-[8px] border border-[#dce3e6] bg-white px-4 text-[13px] font-semibold text-[#00523f] hover:bg-[#f2faf7]">
                Pilih foto produk
            </label>
            <input id="photos" name="photos[]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple class="sr-only" aria-describedby="photo-help photo-count">
            <p id="photo-help" class="mt-2 text-[11px] text-[#718096]">JPG, PNG, atau WEBP · Maks. {{ $photoMaxMb }} MB per foto.</p>
            <p id="photo-count" data-photo-count class="mt-1 text-[11px] text-[#526170]" aria-live="polite">0/{{ $photoMaxCount }} foto baru dipilih.</p>
            @error('photos')
                <p class="mt-2 text-[12px] text-red-600">{{ $message }}</p>
            @enderror
            @error('photos.*')
                <p class="mt-2 text-[12px] text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-5">
        <div class="mb-2 flex items-center justify-between gap-2">
            <label for="video" class="text-[13px] font-semibold text-[#263746]">Video detail <span class="font-normal text-[#718096]">(opsional)</span></label>
            <span class="text-[11px] text-[#718096]">Maks. 1 video</span>
        </div>
        <label for="video" class="flex min-h-11 cursor-pointer items-center justify-center rounded-[8px] border border-dashed border-[#cfd8dc] bg-[#fafbfc] px-4 text-[13px] font-semibold text-[#00523f] hover:bg-[#f2faf7]">Pilih video (MP4, WEBM, MOV)</label>
        <input id="video" name="video" type="file" accept=".mp4,.webm,.mov,video/mp4,video/webm,video/quicktime" class="sr-only" aria-describedby="video-help video-count">
        <p id="video-help" class="mt-2 text-[11px] text-[#718096]">Maks. {{ $videoMaxMb }} MB. Video baru akan menggantikan video lama.</p>
        <div data-new-video-preview class="mt-2"></div>
        <p id="video-count" data-video-count class="mt-1 text-[11px] text-[#526170]" aria-live="polite">Belum ada video baru.</p>
        @error('video')
            <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-5">
        <label for="product_name" class="text-[13px] font-semibold text-[#263746]">Nama barang</label>
        <input id="product_name" name="product_name" type="text" maxlength="{{ config('seklov.products.name_max') }}" value="{{ old('product_name', $product?->product_name) }}" placeholder="Contoh: Jaket kampus ukuran M" class="mt-2 block min-h-11 w-full rounded-[8px] border border-[#dce3e6] px-3.5 text-[14px] outline-none focus:border-[#00523f] focus:ring-2 focus:ring-[#00523f]/10">
        <p class="mt-1 text-[11px] text-[#718096]">Tulis nama yang mudah dicari, termasuk merek atau ukuran bila relevan.</p>
        @error('product_name')
            <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-4">
        <label for="price" class="text-[13px] font-semibold text-[#263746]">Harga barang</label>
        <div class="mt-2 flex min-h-11 items-center rounded-[8px] border border-[#dce3e6] px-3.5 focus-within:border-[#00523f]">
            <span class="mr-3 border-r border-[#e1e6e9] pr-3 text-[14px] text-[#718096]">Rp</span>
            <input id="price" name="price" type="text" inputmode="numeric" value="{{ old('price', $product ? number_format((float) $product->price, 0, '', '.') : '') }}" placeholder="Contoh: 150.000" class="min-w-0 flex-1 border-0 bg-transparent text-[14px] outline-none focus:ring-0">
        </div>
        <p class="mt-1 text-[11px] text-[#718096]">Masukkan harga dalam Rupiah. Batas Rp {{ number_format(config('seklov.products.price_min'), 0, ',', '.') }}–Rp {{ number_format(config('seklov.products.price_max'), 0, ',', '.') }}.</p>
        @error('price')
            <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
            <label for="stock" class="text-[13px] font-semibold text-[#263746]">Stok barang</label>
            <input id="stock" name="stock" type="number" min="{{ $isEditing ? 0 : 1 }}" max="2147483647" step="1" required value="{{ old('stock', $product?->stock) }}" placeholder="Contoh: 5" class="mt-2 block min-h-11 w-full rounded-[8px] border border-[#dce3e6] px-3.5 text-[14px] outline-none focus:border-[#00523f] focus:ring-2 focus:ring-[#00523f]/10">
            <p class="mt-1 text-[11px] text-[#718096]">{{ $isEditing ? 'Gunakan 0 bila stok habis; stok positif mengaktifkan kembali barang terjual.' : 'Jumlah unit yang tersedia untuk dibeli.' }}</p>
            @error('stock')
                <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="is_negotiable" class="text-[13px] font-semibold text-[#263746]">Negosiasi harga</label>
            <select id="is_negotiable" name="is_negotiable" required class="mt-2 block min-h-11 w-full rounded-[8px] border border-[#dce3e6] bg-white px-3.5 text-[14px] outline-none focus:border-[#00523f] focus:ring-2 focus:ring-[#00523f]/10">
                <option value="0" @selected($negotiableValue === '0')>Tidak bisa nego</option>
                <option value="1" @selected($negotiableValue === '1')>Bisa nego</option>
            </select>
            <p class="mt-1 text-[11px] text-[#718096]">Pilih apakah pembeli dapat mengirim tawaran harga.</p>
            @error('is_negotiable')
                <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-4">
        <label for="category_id" class="text-[13px] font-semibold text-[#263746]">Kategori</label>
        <select id="category_id" name="category_id" class="mt-2 block min-h-11 w-full rounded-[8px] border border-[#dce3e6] bg-white px-3.5 text-[14px] outline-none focus:border-[#00523f] focus:ring-2 focus:ring-[#00523f]/10">
            <option value="">Pilih kategori barang</option>
            @foreach ($categories as $category)
                <option value="{{ $category->category_id }}" @selected((string) old('category_id', $product?->category_id) === (string) $category->category_id)>{{ $category->category_name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-[11px] text-[#718096]">Pilih kategori yang paling sesuai dengan barang.</p>
        @error('category_id')
            <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-4">
        <label for="condition" class="text-[13px] font-semibold text-[#263746]">Kondisi barang</label>
        <select id="condition" name="condition" class="mt-2 block min-h-11 w-full rounded-[8px] border border-[#dce3e6] bg-white px-3.5 text-[14px] outline-none focus:border-[#00523f] focus:ring-2 focus:ring-[#00523f]/10">
            <option value="">Pilih kondisi barang</option>
            @foreach (config('seklov.products.conditions') as $condition)
                <option value="{{ $condition }}" @selected(old('condition', $product?->condition) === $condition)>{{ $condition }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-[11px] text-[#718096]">Pilih kondisi yang paling jujur menggambarkan barang.</p>
        @error('condition')
            <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
            <label for="size" class="text-[13px] font-semibold text-[#263746]">Ukuran <span class="font-normal text-[#718096]">(opsional)</span></label>
            <input id="size" name="size" type="text" value="{{ old('size', $product?->size) }}" placeholder="Contoh: M, 38, 15 inci" class="mt-2 block min-h-11 w-full rounded-[8px] border border-[#dce3e6] px-3.5 text-[14px] outline-none focus:border-[#00523f] focus:ring-2 focus:ring-[#00523f]/10">
            <p class="mt-1 text-[11px] text-[#718096]">Isi ukuran pakaian, sepatu, atau dimensi barang.</p>
            @error('size')
                <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="usage_period" class="text-[13px] font-semibold text-[#263746]">Lama pemakaian <span class="font-normal text-[#718096]">(opsional)</span></label>
            <input id="usage_period" name="usage_period" type="text" value="{{ old('usage_period', $product?->usage_period) }}" placeholder="Contoh: 6 bulan" class="mt-2 block min-h-11 w-full rounded-[8px] border border-[#dce3e6] px-3.5 text-[14px] outline-none focus:border-[#00523f] focus:ring-2 focus:ring-[#00523f]/10">
            <p class="mt-1 text-[11px] text-[#718096]">Ceritakan berapa lama barang telah digunakan.</p>
            @error('usage_period')
                <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-4">
        <label for="defects" class="text-[13px] font-semibold text-[#263746]">Kekurangan / minus <span class="font-normal text-[#718096]">(jika ada)</span></label>
        <textarea id="defects" name="defects" rows="2" placeholder="Contoh: Ada gores kecil di sisi kanan." class="mt-2 block w-full rounded-[8px] border border-[#dce3e6] px-3.5 py-3 text-[14px] outline-none focus:border-[#00523f] focus:ring-2 focus:ring-[#00523f]/10">{{ old('defects', $product?->defects) }}</textarea>
        <p class="mt-1 text-[11px] text-[#718096]">Wajib dijelaskan bila kondisi barang “Ada kekurangan”.</p>
        @error('defects')
            <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-4">
        <label for="description" class="text-[13px] font-semibold text-[#263746]">Deskripsi barang</label>
        <textarea id="description" name="description" rows="4" maxlength="{{ config('seklov.products.description_max') }}" placeholder="Ceritakan bahan, fungsi, keadaan, dan hal penting lainnya. Contoh: Jaket bahan fleece, dipakai sekitar 6 bulan, resleting berfungsi baik." class="mt-2 block w-full rounded-[8px] border border-[#dce3e6] px-3.5 py-3 text-[14px] leading-5 outline-none focus:border-[#00523f] focus:ring-2 focus:ring-[#00523f]/10">{{ old('description', $product?->description) }}</textarea>
        <p class="mt-1 text-[11px] text-[#718096]">Minimal {{ config('seklov.products.description_min') }} karakter. Sebutkan keadaan dan detail yang akan ditanyakan pembeli.</p>
        @error('description')
            <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-6 flex flex-wrap gap-3">
        <button type="submit" class="min-h-11 flex-1 rounded-[9px] bg-[#00523f] px-5 py-2.5 text-[14px] font-semibold text-white hover:bg-[#006c52]">
            {{ $isEditing ? 'Simpan Perubahan' : 'Jual Sekarang' }}
        </button>
        <a href="{{ $isEditing ? route('products.show', $product) : route('home') }}" class="inline-flex min-h-11 items-center justify-center rounded-[9px] border border-[#dce3e6] px-5 py-2.5 text-[14px] font-semibold text-[#00523f] hover:bg-[#f7f9fa]">Batal</a>
    </div>
</form>
