<?php

namespace App\Http\Requests;

use App\Models\Negotiation;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOfferRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $price = $this->input('offered_price');

        if (is_string($price)) {
            $this->merge([
                'offered_price' => str_replace(['.', ' '], '', trim($price)),
            ]);
        }
    }

    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            && ($this->user()?->can('create', [Negotiation::class, $product]) ?? false);
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $minimumPrice = config('seklov.negotiations.minimum_price');
        $minimumPercent = config('seklov.negotiations.minimum_price_percent');

        if ($product instanceof Product) {
            $minimumPrice = max(
                $minimumPrice,
                (int) ceil((float) $product->price * $minimumPercent / 100),
            );
        }

        return [
            'offered_price' => [
                'required',
                'integer',
                'min:'.$minimumPrice,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'offered_price.required' => 'Masukkan harga penawaranmu.',
            'offered_price.integer' => 'Harga penawaran harus berupa angka bulat dalam Rupiah.',
            'offered_price.min' => 'Tawaran minimal Rp :min sesuai batas negosiasi barang.',
        ];
    }

    public function attributes(): array
    {
        return [
            'offered_price' => 'harga penawaran',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $product = $this->route('product');
            $price = $this->input('offered_price');

            if ($product instanceof Product && is_numeric($price) && (int) $price > (int) $product->price) {
                $validator->errors()->add('offered_price', 'Tawaran tidak boleh lebih besar dari harga barang.');
            }
        });
    }

    protected function getRedirectUrl(): string
    {
        return route('products.show', [
            'product' => $this->route('product'),
            'nego' => 'open',
        ]);
    }
}
