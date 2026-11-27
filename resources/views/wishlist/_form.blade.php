@php
    $w = $wishlist ?? null;
    $input = 'h-11 w-full rounded-[8px] border border-[#e1e6e9] bg-[#f7f9fa] px-3 text-[14px] text-[#202b3d] placeholder:text-[#a0aab4] focus:border-[#00523f] focus:bg-white focus:outline-none focus:ring-0';
    $label = 'mb-1.5 block text-[14px] font-semibold text-[#263746]';
    $error = 'mt-1 text-[14px] text-[#e53e3e]';
@endphp

<form method="POST" action="{{ $w ? route('wishlist.update', $w) : route('wishlist.store') }}" enctype="multipart/form-data"
      class="mx-auto max-w-xl space-y-5 rounded-[18px] border border-[#e7ebee] bg-white p-5 shadow-[0_3px_12px_rgba(22,34,48,0.04)] sm:p-8">
    @csrf
    @if ($w)
        @method('PUT')
    @endif

    <div>
        <label for="item_name" class="{{ $label }}">Nama barang</label>
        <input id="item_name" type="text" name="item_name" value="{{ old('item_name', $w?->item_name) }}" placeholder="Contoh: Kemeja flanel ukuran M" class="{{ $input }}">
        @error('item_name') <p class="{{ $error }}">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="category_id" class="{{ $label }}">Kategori</label>
            <select id="category_id" name="category_id" class="{{ $input }}">
                <option value="">Pilih kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->category_id }}" @selected((string) old('category_id', $w?->category_id) === (string) $category->category_id)>{{ $category->category_name }}</option>
                @endforeach
            </select>
            @error('category_id') <p class="{{ $error }}">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="condition" class="{{ $label }}">Kondisi yang dicari</label>
            <select id="condition" name="condition" class="{{ $input }}">
                <option value="">Pilih kondisi</option>
                @foreach ($conditions as $condition)
                    <option value="{{ $condition }}" @selected(old('condition', $w?->condition) === $condition)>{{ $condition }}</option>
                @endforeach
            </select>
            @error('condition') <p class="{{ $error }}">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="budget" class="{{ $label }}">Budget maksimal</label>
        <div class="flex h-11 items-center overflow-hidden rounded-[8px] border border-[#e1e6e9] bg-[#f7f9fa] focus-within:border-[#00523f] focus-within:bg-white">
            <span class="flex h-full items-center border-r border-[#e1e6e9] px-3 text-[13px] font-medium text-[#718096]">Rp</span>
            <input id="budget" type="number" name="budget" min="0" inputmode="numeric" value="{{ old('budget', $w ? (int) $w->budget : '') }}" placeholder="0" class="h-full w-full min-w-0 border-0 bg-transparent px-3 text-[14px] focus:ring-0">
        </div>
        @error('budget') <p class="{{ $error }}">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="description" class="{{ $label }}">Deskripsi (opsional)</label>
        <textarea id="description" name="description" rows="3" placeholder="Ukuran, warna, atau detail lain" class="w-full rounded-[8px] border border-[#e1e6e9] bg-[#f7f9fa] px-3 py-2.5 text-[14px] placeholder:text-[#a0aab4] focus:border-[#00523f] focus:bg-white focus:outline-none focus:ring-0">{{ old('description', $w?->description) }}</textarea>
        @error('description') <p class="{{ $error }}">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="image" class="{{ $label }}">Foto referensi (opsional)</label>
        <input id="image" type="file" name="image" accept="image/*" class="block w-full text-[14px] text-[#526170] file:mr-3 file:rounded-[8px] file:border-0 file:bg-[#edf7f4] file:px-4 file:py-2 file:text-[14px] file:font-semibold file:text-[#00523f]">
        @if ($w?->image)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($w->image) }}" alt="Foto saat ini" class="mt-3 h-28 w-full max-w-[280px] rounded-[10px] object-cover">
        @endif
        @error('image') <p class="{{ $error }}">{{ $message }}</p> @enderror
    </div>

    @unless ($w)
        <div>
            <label for="durasi" class="{{ $label }}">Berlaku selama</label>
            <select id="durasi" name="durasi" class="{{ $input }}">
                @foreach ([7 => '7 hari', 14 => '14 hari', 30 => '30 hari'] as $value => $text)
                    <option value="{{ $value }}" @selected((string) old('durasi', 30) === (string) $value)>{{ $text }}</option>
                @endforeach
            </select>
            @error('durasi') <p class="{{ $error }}">{{ $message }}</p> @enderror
        </div>
    @endunless

    <div class="flex flex-wrap items-center gap-3 pt-2">
        <button type="submit" class="min-h-11 flex-1 rounded-[10px] bg-[#00523f] px-6 text-[14px] font-semibold text-white transition hover:bg-[#006c52] sm:flex-none">Simpan</button>
        <a href="{{ route('profile.me', ['tab' => 'wishlist']) }}" class="inline-flex min-h-11 items-center px-3 text-[14px] font-medium text-[#718096] hover:text-[#00523f]">Batal</a>
    </div>
</form>