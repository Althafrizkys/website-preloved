<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,product_id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.exists' => 'Barang tidak ditemukan.',
            'quantity.min' => 'Jumlah minimal 1 unit.',
        ];
    }
}
