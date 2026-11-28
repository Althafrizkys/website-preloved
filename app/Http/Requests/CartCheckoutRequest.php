<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $rules = [
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['integer', 'distinct'],
        ];

        if ($this->isMethod('post')) {
            $rules['meeting_point_id'] = ['required', 'integer', 'exists:campus_meeting_points,meeting_point_id'];
            $rules['buyer_notes'] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Pilih minimal satu barang untuk checkout.',
            'items.min' => 'Pilih minimal satu barang untuk checkout.',
            'meeting_point_id.required' => 'Pilih titik temu COD terlebih dahulu.',
            'meeting_point_id.exists' => 'Titik temu COD tidak ditemukan.',
            'buyer_notes.max' => 'Catatan maksimal 500 karakter.',
        ];
    }
}
