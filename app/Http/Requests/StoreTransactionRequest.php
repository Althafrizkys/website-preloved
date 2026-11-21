<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,product_id'],
            'meeting_point_id' => ['required', 'integer', 'exists:campus_meeting_points,meeting_point_id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'buyer_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Barang yang dipesan wajib dipilih.',
            'meeting_point_id.required' => 'Titik temu COD wajib dipilih.',
            'meeting_point_id.exists' => 'Titik temu COD tidak valid.',
            'quantity.min' => 'Jumlah pesanan minimal 1.',
            'quantity.max' => 'Jumlah pesanan maksimal 10.',
            'buyer_notes.max' => 'Catatan untuk penjual maksimal 500 karakter.',
        ];
    }
}
