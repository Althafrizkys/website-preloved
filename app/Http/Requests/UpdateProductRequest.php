<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            && ($this->user()?->can('update', $product) ?? false);
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $product = $this->route('product');

        $rules['photos'] = ['sometimes', 'array', 'max:'.config('seklov.products.max_photos')];
        $rules['stock'] = ['required', 'integer', 'min:0', 'max:2147483647'];
        $rules['stock_original'] = ['required', 'integer', 'min:0', 'max:2147483647'];
        $rules['photos.*'] = [
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:'.config('seklov.products.photo_max_kb'),
        ];
        $rules['delete_media'] = ['sometimes', 'array'];
        $rules['delete_media.*'] = [
            'required',
            'integer',
            'distinct',
            Rule::exists('product_media', 'media_id')->where('product_id', $product?->product_id),
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'stock_original.required' => 'Data stok awal tidak valid. Muat ulang halaman.',
            'stock_original.integer' => 'Data stok awal tidak valid. Muat ulang halaman.',
            'stock_original.min' => 'Data stok awal tidak valid. Muat ulang halaman.',
            'stock_original.max' => 'Data stok awal tidak valid. Muat ulang halaman.',
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'stock_original' => 'stok awal',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $product = $this->route('product');
            if (! $product instanceof Product) {
                return;
            }

            $deleteIds = array_map('intval', $this->input('delete_media', []));
            $deletedPhotos = $product->media()
                ->where('media_type', 'foto')
                ->whereIn('media_id', $deleteIds)
                ->count();
            $existingPhotos = $product->media()->where('media_type', 'foto')->count();
            $newPhotos = count($this->file('photos', []));
            $photoCount = $existingPhotos - $deletedPhotos + $newPhotos;

            if ($photoCount < 1) {
                $validator->errors()->add('photos', 'Produk harus memiliki minimal satu foto.');
            } elseif ($photoCount > config('seklov.products.max_photos')) {
                $validator->errors()->add(
                    'photos',
                    'Jumlah foto produk setelah perubahan maksimal '.config('seklov.products.max_photos').' file.',
                );
            }

            $existingVideos = $product->media()->where('media_type', 'video')->count();
            $deletedVideos = $product->media()
                ->where('media_type', 'video')
                ->whereIn('media_id', $deleteIds)
                ->count();
            $remainingVideos = $this->hasFile('video')
                ? 1
                : $existingVideos - $deletedVideos;

            if ($remainingVideos > 1) {
                $validator->errors()->add('video', 'Produk hanya dapat memiliki satu video.');
            }
        });
    }
}
