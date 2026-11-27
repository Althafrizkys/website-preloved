<?php

namespace App\Http\Requests;

use App\Models\Negotiation;
use App\Models\Product;
use App\Models\TransactionItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['nullable', 'integer', 'exists:products,product_id'],
            'meeting_point_id' => ['required', 'integer', 'exists:campus_meeting_points,meeting_point_id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'negotiation_id' => ['nullable', 'integer', 'exists:negotiations,negotiation_id'],
            'buyer_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Barang yang dipesan wajib dipilih.',
            'product_id.exists' => 'Barang yang dipesan tidak ditemukan.',
            'meeting_point_id.required' => 'Titik temu COD wajib dipilih.',
            'meeting_point_id.exists' => 'Titik temu COD tidak valid.',
            'quantity.min' => 'Jumlah pesanan minimal 1.',
            'quantity.integer' => 'Jumlah pesanan harus berupa angka bulat.',
            'quantity.required' => 'Jumlah pesanan wajib diisi.',
            'negotiation_id.integer' => 'Referensi tawaran nego tidak valid.',
            'buyer_notes.max' => 'Catatan untuk penjual maksimal 500 karakter.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! $this->filled('product_id')) {
                if (! $this->filled('negotiation_id')) {
                    $validator->errors()->add('product_id', 'Barang yang dipesan wajib dipilih.');

                    return;
                }

                $offer = Negotiation::query()
                    ->with('conversation')
                    ->find($this->input('negotiation_id'));
                $conversation = $offer?->conversation;

                if (
                    ! $offer
                    || $offer->status !== 'diterima'
                    || $offer->product_id !== null
                    || ! $offer->offered_item_name
                    || $conversation?->wishlist_request_id === null
                    || (int) $conversation?->buyer_id !== (int) $this->user()->user_id
                    || (int) $conversation?->seller_id !== (int) $offer->sender_id
                ) {
                    $validator->errors()->add('negotiation_id', 'Kesepakatan wishlist tidak berlaku.');

                    return;
                }

                if ((int) $this->input('quantity') !== 1) {
                    $validator->errors()->add('quantity', 'Jumlah barang wishlist harus 1.');
                }

                if (TransactionItem::query()->where('negotiation_id', $offer->negotiation_id)->exists()) {
                    $validator->errors()->add('negotiation_id', 'Kesepakatan ini sudah digunakan untuk transaksi.');
                }

                return;
            }

            $product = Product::query()->find($this->input('product_id'));
            $quantity = (int) $this->input('quantity');

            if (! $product || $product->status !== 'tersedia' || $product->stock < 1) {
                $validator->errors()->add('quantity', 'Stok barang ini sudah habis atau tidak tersedia.');

                return;
            }

            if ($quantity > $product->stock) {
                $validator->errors()->add(
                    'quantity',
                    'Jumlah pesanan melebihi stok yang tersedia ('.$product->stock.' unit).',
                );
            }
        });
    }
}
