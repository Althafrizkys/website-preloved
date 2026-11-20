<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $price = $this->input('price');

        if (is_string($price)) {
            $this->merge([
                'price' => str_replace(['.', ' '], '', trim($price)),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:'.config('seklov.products.name_max')],
            'price' => [
                'required',
                'integer',
                'min:'.config('seklov.products.price_min'),
                'max:'.config('seklov.products.price_max'),
            ],
            'category_id' => ['required', 'integer', 'exists:categories,category_id'],
            'condition' => ['required', Rule::in(config('seklov.products.conditions'))],
            'size' => ['nullable', 'string', 'max:100'],
            'usage_period' => ['nullable', 'string', 'max:100'],
            'defects' => ['nullable', 'required_if:condition,Ada kekurangan', 'string', 'max:2000'],
            'description' => [
                'required',
                'string',
                'min:'.config('seklov.products.description_min'),
                'max:'.config('seklov.products.description_max'),
            ],
            'photos' => ['required', 'array', 'min:1', 'max:'.config('seklov.products.max_photos')],
            'photos.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config('seklov.products.photo_max_kb'),
            ],
            'video' => [
                'nullable',
                'file',
                'mimes:mp4,webm,mov',
                'max:'.config('seklov.products.video_max_kb'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'product_name.required' => 'Nama barang wajib diisi.',
            'product_name.max' => 'Nama barang maksimal :max karakter.',
            'price.required' => 'Harga barang wajib diisi.',
            'price.integer' => 'Harga harus berupa angka bulat dalam Rupiah.',
            'price.min' => 'Harga minimal Rp '.number_format(config('seklov.products.price_min'), 0, ',', '.').'.',
            'price.max' => 'Harga maksimal Rp '.number_format(config('seklov.products.price_max'), 0, ',', '.').'.',
            'category_id.required' => 'Pilih kategori barang.',
            'category_id.exists' => 'Kategori yang dipilih tidak tersedia.',
            'condition.required' => 'Pilih kondisi barang.',
            'condition.in' => 'Kondisi barang yang dipilih tidak valid.',
            'size.max' => 'Ukuran maksimal :max karakter.',
            'usage_period.max' => 'Lama pemakaian maksimal :max karakter.',
            'defects.required_if' => 'Jelaskan kekurangan barang ini.',
            'defects.max' => 'Keterangan kekurangan maksimal :max karakter.',
            'description.required' => 'Deskripsi barang wajib diisi.',
            'description.min' => 'Deskripsi minimal :min karakter agar informasi barang jelas.',
            'description.max' => 'Deskripsi maksimal :max karakter.',
            'photos.required' => 'Unggah minimal satu foto barang.',
            'photos.array' => 'Format daftar foto tidak valid.',
            'photos.min' => 'Unggah minimal satu foto barang.',
            'photos.max' => 'Foto barang maksimal :max file.',
            'photos.*.required' => 'Setiap foto harus berupa file.',
            'photos.*.image' => 'Setiap file foto harus berupa gambar.',
            'photos.*.mimes' => 'Format foto yang didukung: JPG, JPEG, PNG, atau WEBP.',
            'photos.*.max' => 'Ukuran setiap foto maksimal '.number_format(config('seklov.products.photo_max_kb') / 1024, 0).' MB.',
            'video.file' => 'Video detail harus berupa file.',
            'video.mimes' => 'Format video yang didukung: MP4, WEBM, atau MOV.',
            'video.max' => 'Ukuran video maksimal '.number_format(config('seklov.products.video_max_kb') / 1024, 0).' MB.',
            'delete_media.array' => 'Daftar media yang akan dihapus tidak valid.',
            'delete_media.*.required' => 'Media yang akan dihapus tidak valid.',
            'delete_media.*.integer' => 'Media yang akan dihapus tidak valid.',
            'delete_media.*.distinct' => 'Media yang sama tidak dapat dipilih lebih dari sekali.',
            'delete_media.*.exists' => 'Media tersebut bukan bagian dari produk ini.',
        ];
    }

    public function attributes(): array
    {
        return [
            'product_name' => 'nama barang',
            'price' => 'harga',
            'category_id' => 'kategori',
            'condition' => 'kondisi',
            'size' => 'ukuran',
            'usage_period' => 'lama pemakaian',
            'defects' => 'kekurangan',
            'description' => 'deskripsi',
            'photos' => 'foto',
            'video' => 'video',
        ];
    }
}
